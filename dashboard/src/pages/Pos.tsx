import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  Banknote,
  Clock,
  CreditCard,
  Minus,
  Plus,
  Search,
  ShoppingBag,
  Smartphone,
  Trash2,
  Wallet,
} from 'lucide-react';
import {
  adminProductsService,
  getErrorMessage,
  ordersService,
  promosService,
  usersService,
} from '@/api';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/Button';
import { LoadingState, ErrorState, EmptyState } from '@/components/States';
import { useLocaleStore } from '@/store/locale';
import { toast } from '@/store/toast';
import { formatMoney, hexArgbToCss, hexArgbToInt } from '@/lib/format';
import type {
  AdminProduct,
  Order,
  OrderPayment,
  PosPaymentMethod,
  PosSaleInput,
} from '@/types';

// Money is compared in piastres so 0.1 + 0.2 never blocks a sale.
const cents = (v: number) => Math.round(v * 100);

// A product with no size/colour variants still needs a value on the line.
const NO_SIZE = 'One Size';

interface CartLine {
  key: string; // product_id|size|color — one line per variant
  product: AdminProduct;
  size: string;
  color: string; // #AARRGGBB ('' when the product has no colours)
  quantity: number;
}

const lineKey = (productId: string, size: string, color: string) =>
  `${productId}|${size}|${color}`;

const PAYMENT_METHODS: {
  value: PosPaymentMethod;
  label: string;
  icon: typeof Banknote;
}[] = [
  { value: 'cash', label: 'Cash', icon: Banknote },
  { value: 'instapay', label: 'InstaPay', icon: Smartphone },
  { value: 'wallet', label: 'Wallet', icon: Wallet },
  { value: 'creditCard', label: 'Visa / Card', icon: CreditCard },
  { value: 'deferred', label: 'Pay later', icon: Clock },
];


export function Pos() {
  const { t, locale } = useLocaleStore();
  const qc = useQueryClient();

  const [search, setSearch] = useState('');
  const [lines, setLines] = useState<CartLine[]>([]);
  // Tenders the cashier has entered. Empty = collect it all as cash.
  const [tenders, setTenders] = useState<OrderPayment[]>([]);
  const [customerName, setCustomerName] = useState('');
  const [customerPhone, setCustomerPhone] = useState('');
  const [userId, setUserId] = useState<string>('');
  const [promo, setPromo] = useState('');
  const [lastSale, setLastSale] = useState<Order | null>(null);

  const productsQuery = useQuery({
    queryKey: ['admin-products', { search }],
    queryFn: () => adminProductsService.list({ search: search || undefined }),
  });

  const customersQuery = useQuery({
    queryKey: ['users'],
    queryFn: () => usersService.list(),
  });

  // Only to mirror the server's discount in the split maths — the server still
  // recomputes every total and rejects tenders that don't add up.
  const promosQuery = useQuery({
    queryKey: ['promos'],
    queryFn: () => promosService.list(),
  });

  const addProduct = (product: AdminProduct) => {
    const size = product.sizes[0] ?? NO_SIZE;
    const color = product.colors[0] ?? '';
    const key = lineKey(product.id, size, color);

    setLines((prev) => {
      const existing = prev.find((l) => l.key === key);
      if (existing) {
        return prev.map((l) =>
          l.key === key ? { ...l, quantity: l.quantity + 1 } : l,
        );
      }
      return [...prev, { key, product, size, color, quantity: 1 }];
    });
  };

  const setQuantity = (key: string, quantity: number) =>
    setLines((prev) =>
      quantity <= 0
        ? prev.filter((l) => l.key !== key)
        : prev.map((l) => (l.key === key ? { ...l, quantity } : l)),
    );

  // Changing a variant re-keys the line, so merge if that variant already exists.
  const setVariant = (key: string, patch: Partial<Pick<CartLine, 'size' | 'color'>>) =>
    setLines((prev) => {
      const line = prev.find((l) => l.key === key);
      if (!line) return prev;

      const next = { ...line, ...patch };
      next.key = lineKey(next.product.id, next.size, next.color);

      const rest = prev.filter((l) => l.key !== key);
      const clash = rest.find((l) => l.key === next.key);
      if (clash) {
        return rest.map((l) =>
          l.key === next.key
            ? { ...l, quantity: l.quantity + next.quantity }
            : l,
        );
      }
      return prev.map((l) => (l.key === key ? next : l));
    });

  // Display only — the server recomputes every total from the catalog.
  const subtotal = useMemo(
    () => lines.reduce((sum, l) => sum + l.product.price * l.quantity, 0),
    [lines],
  );
  const itemCount = lines.reduce((n, l) => n + l.quantity, 0);
  const currency = lines[0]?.product.currency ?? 'EGP';

  const appliedPromo = useMemo(() => {
    const code = promo.trim().toUpperCase();
    if (!code) return null;
    return (
      (promosQuery.data ?? []).find(
        (p) => p.code.toUpperCase() === code && p.active,
      ) ?? null
    );
  }, [promo, promosQuery.data]);

  const discount = appliedPromo ? subtotal * appliedPromo.fraction : 0;
  const total = Math.max(0, subtotal - discount);

  // With no tenders entered, the sale is simply all cash.
  const collected = tenders.reduce((s, t) => s + (t.amount || 0), 0);
  const remaining = tenders.length === 0 ? 0 : total - collected;
  const balanced = cents(remaining) === 0;

  const addTender = (method: PosPaymentMethod) =>
    setTenders((prev) => {
      // First tender defaults to the whole balance; later ones to what's left.
      const outstanding =
        total - prev.reduce((s, t) => s + (t.amount || 0), 0);
      return [...prev, { method, amount: Math.max(0, Number(outstanding.toFixed(2))) }];
    });

  const setTender = (index: number, patch: Partial<OrderPayment>) =>
    setTenders((prev) =>
      prev.map((t, i) => (i === index ? { ...t, ...patch } : t)),
    );

  const removeTender = (index: number) =>
    setTenders((prev) => prev.filter((_, i) => i !== index));

  const reset = () => {
    setLines([]);
    setTenders([]);
    setPromo('');
    setCustomerName('');
    setCustomerPhone('');
    setUserId('');
  };

  const saleMutation = useMutation({
    mutationFn: (input: PosSaleInput) => ordersService.createPosSale(input),
    onSuccess: (order) => {
      qc.invalidateQueries({ queryKey: ['orders'] });
      qc.invalidateQueries({ queryKey: ['admin-products'] }); // stock changed
      qc.invalidateQueries({ queryKey: ['dashboard-stats'] });
      setLastSale(order);
      reset();
      toast.success(`Sale ${order.id} recorded`);
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const completeSale = () => {
    if (lines.length === 0 || !balanced) return;
    saleMutation.mutate({
      items: lines.map((l) => ({
        product_id: l.product.id,
        size: l.size,
        color_value: l.color ? hexArgbToInt(l.color) : 0,
        quantity: l.quantity,
      })),
      // No explicit tenders = the whole sale in cash.
      payments:
        tenders.length > 0
          ? tenders.map((t) => ({ ...t, amount: Number(t.amount.toFixed(2)) }))
          : [{ method: 'cash', amount: Number(total.toFixed(2)) }],
      user_id: userId ? Number(userId) : null,
      customer_name: customerName.trim() || null,
      customer_phone: customerPhone.trim() || null,
      promo_code: promo.trim() || null,
    });
  };

  const products = productsQuery.data ?? [];

  return (
    <div>
      <PageHeader title="Cashier (POS)" subtitle="Ring up an in-store sale" />

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_380px]">
        {/* ---------------- Catalog ---------------- */}
        <div>
          <div className="card mb-4 p-4">
            <div className="relative">
              <Search
                size={16}
                className="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-slate-400"
              />
              <input
                className="input ps-9"
                placeholder={t('search')}
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                autoFocus
              />
            </div>
          </div>

          <div className="card p-3">
            {productsQuery.isLoading ? (
              <LoadingState />
            ) : productsQuery.error ? (
              <ErrorState
                message={getErrorMessage(productsQuery.error)}
                onRetry={() => productsQuery.refetch()}
              />
            ) : products.length === 0 ? (
              <EmptyState label="No products found" />
            ) : (
              <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                {products.map((p) => {
                  const out = (p.stock ?? 0) <= 0;
                  return (
                    <button
                      key={p.id}
                      type="button"
                      disabled={out}
                      onClick={() => addProduct(p)}
                      className="group rounded-xl border border-slate-200 p-2 text-start transition hover:border-brand-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700"
                    >
                      {p.images[0] ? (
                        <img
                          src={p.images[0]}
                          alt=""
                          loading="lazy"
                          className="mb-2 aspect-square w-full rounded-lg object-cover"
                        />
                      ) : (
                        <div className="mb-2 flex aspect-square w-full items-center justify-center rounded-lg bg-slate-100 text-slate-300 dark:bg-slate-800">
                          <ShoppingBag size={22} />
                        </div>
                      )}
                      <p className="truncate text-sm font-medium">
                        {p.name[locale] || p.name.en}
                      </p>
                      <div className="mt-0.5 flex items-center justify-between">
                        <span className="text-sm text-brand-700 dark:text-brand-500">
                          {formatMoney(p.price, p.currency)}
                        </span>
                        <span
                          className={`text-xs ${out ? 'text-rose-500' : 'text-slate-400'}`}
                        >
                          {out ? 'Out' : `${p.stock ?? 0} left`}
                        </span>
                      </div>
                    </button>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* ---------------- Ticket ---------------- */}
        <div className="card flex h-fit flex-col p-4 lg:sticky lg:top-4">
          <h3 className="mb-3 flex items-center gap-2 font-semibold">
            <ShoppingBag size={18} /> Current sale
            {itemCount > 0 && (
              <span className="ms-auto rounded-full bg-brand-700 px-2 py-0.5 text-xs text-white">
                {itemCount}
              </span>
            )}
          </h3>

          {lines.length === 0 ? (
            <p className="rounded-lg border border-dashed border-slate-300 px-3 py-8 text-center text-sm text-slate-400 dark:border-slate-700">
              Tap a product to add it.
            </p>
          ) : (
            <ul className="mb-3 max-h-[40vh] space-y-2 overflow-y-auto">
              {lines.map((l) => (
                <li
                  key={l.key}
                  className="rounded-lg border border-slate-200 p-2 dark:border-slate-700"
                >
                  <div className="flex items-start gap-2">
                    <span className="flex-1 truncate text-sm font-medium">
                      {l.product.name[locale] || l.product.name.en}
                    </span>
                    <button
                      type="button"
                      onClick={() => setQuantity(l.key, 0)}
                      className="rounded p-1 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950"
                      aria-label="Remove"
                    >
                      <Trash2 size={14} />
                    </button>
                  </div>

                  <div className="mt-2 flex flex-wrap items-center gap-2">
                    {l.product.sizes.length > 0 && (
                      <select
                        className="input h-8 w-auto py-0 text-xs"
                        value={l.size}
                        onChange={(e) =>
                          setVariant(l.key, { size: e.target.value })
                        }
                      >
                        {l.product.sizes.map((s) => (
                          <option key={s} value={s}>
                            {s}
                          </option>
                        ))}
                      </select>
                    )}

                    {l.product.colors.length > 0 && (
                      <div className="flex items-center gap-1">
                        {l.product.colors.map((c) => (
                          <button
                            key={c}
                            type="button"
                            onClick={() => setVariant(l.key, { color: c })}
                            style={{ background: hexArgbToCss(c) }}
                            className={`h-6 w-6 rounded-full border-2 ${
                              l.color === c
                                ? 'border-brand-700'
                                : 'border-slate-200 dark:border-slate-700'
                            }`}
                            aria-label={c}
                          />
                        ))}
                      </div>
                    )}

                    <div className="ms-auto flex items-center gap-1">
                      <button
                        type="button"
                        onClick={() => setQuantity(l.key, l.quantity - 1)}
                        className="rounded border border-slate-200 p-1 dark:border-slate-700"
                        aria-label="Decrease"
                      >
                        <Minus size={13} />
                      </button>
                      <span className="w-6 text-center text-sm">
                        {l.quantity}
                      </span>
                      <button
                        type="button"
                        onClick={() => setQuantity(l.key, l.quantity + 1)}
                        className="rounded border border-slate-200 p-1 dark:border-slate-700"
                        aria-label="Increase"
                      >
                        <Plus size={13} />
                      </button>
                    </div>
                  </div>

                  <p className="mt-1 text-end text-xs text-slate-400">
                    {formatMoney(l.product.price * l.quantity, l.product.currency)}
                  </p>
                </li>
              ))}
            </ul>
          )}

          {/* Customer */}
          <div className="space-y-2 border-t border-slate-100 pt-3 dark:border-slate-800">
            <label className="label mb-0">Customer (optional)</label>
            <select
              className="input"
              value={userId}
              onChange={(e) => setUserId(e.target.value)}
            >
              <option value="">Walk-in (no account)</option>
              {(customersQuery.data ?? []).map((u) => (
                <option key={u.id} value={u.id}>
                  {u.name} {u.phone ? `· ${u.phone}` : ''}
                </option>
              ))}
            </select>
            {!userId && (
              <div className="grid grid-cols-2 gap-2">
                <input
                  className="input"
                  placeholder="Name"
                  value={customerName}
                  onChange={(e) => setCustomerName(e.target.value)}
                />
                <input
                  className="input"
                  placeholder="Phone"
                  value={customerPhone}
                  onChange={(e) => setCustomerPhone(e.target.value)}
                />
              </div>
            )}
          </div>

          {/* Promo */}
          <div className="mt-3">
            <label className="label">Promo code (optional)</label>
            <input
              className="input uppercase"
              placeholder="e.g. MODIST10"
              value={promo}
              onChange={(e) => setPromo(e.target.value.toUpperCase())}
            />
          </div>

          {/* Totals */}
          <div className="mt-4 space-y-1 border-t border-slate-100 pt-3 text-sm dark:border-slate-800">
            <div className="flex items-center justify-between text-slate-500">
              <span>
                Subtotal ({itemCount} item{itemCount === 1 ? '' : 's'})
              </span>
              <span>{formatMoney(subtotal, currency)}</span>
            </div>
            {discount > 0 && (
              <div className="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                <span>Discount ({appliedPromo?.code})</span>
                <span>− {formatMoney(discount, currency)}</span>
              </div>
            )}
            {promo.trim() !== '' && !appliedPromo && (
              <p className="text-xs text-amber-600 dark:text-amber-400">
                Unknown or inactive code — the server will ignore it.
              </p>
            )}
            <div className="flex items-center justify-between pt-1 text-base font-bold">
              <span>Total</span>
              <span>{formatMoney(total, currency)}</span>
            </div>
          </div>

          {/* Payment — one row per method, so a sale can be split */}
          <div className="mt-3">
            <div className="mb-2 flex items-center justify-between">
              <label className="label mb-0">Payment</label>
              {tenders.length > 0 && (
                <button
                  type="button"
                  className="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                  onClick={() => setTenders([])}
                >
                  Reset to all cash
                </button>
              )}
            </div>

            {tenders.length === 0 ? (
              <p className="mb-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                Collecting the full amount in <strong>cash</strong>. Add a method
                below to split it.
              </p>
            ) : (
              <ul className="mb-2 space-y-2">
                {tenders.map((t, i) => (
                  <li key={i} className="flex items-center gap-2">
                    <select
                      className="input h-9 flex-1 py-0 text-xs"
                      value={t.method}
                      onChange={(e) =>
                        setTender(i, {
                          method: e.target.value as PosPaymentMethod,
                        })
                      }
                    >
                      {PAYMENT_METHODS.map((m) => (
                        <option key={m.value} value={m.value}>
                          {m.label}
                        </option>
                      ))}
                    </select>
                    <input
                      type="number"
                      step="0.01"
                      min="0"
                      className="input h-9 w-24 py-0 text-xs"
                      value={t.amount}
                      onChange={(e) =>
                        setTender(i, { amount: Number(e.target.value) })
                      }
                    />
                    <button
                      type="button"
                      onClick={() => removeTender(i)}
                      className="rounded p-1 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950"
                      aria-label="Remove payment"
                    >
                      <Trash2 size={14} />
                    </button>
                  </li>
                ))}
              </ul>
            )}

            <div className="flex flex-wrap gap-1.5">
              {PAYMENT_METHODS.map(({ value, label, icon: Icon }) => (
                <button
                  key={value}
                  type="button"
                  onClick={() => addTender(value)}
                  className="flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-medium text-slate-600 transition hover:border-brand-700 hover:text-brand-700 dark:border-slate-700 dark:text-slate-300"
                >
                  <Icon size={13} />
                  {label}
                </button>
              ))}
            </div>

            {tenders.length > 0 && !balanced && (
              <p
                className={`mt-2 rounded-lg px-3 py-2 text-xs font-medium ${
                  remaining > 0
                    ? 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400'
                    : 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-400'
                }`}
              >
                {remaining > 0
                  ? `${formatMoney(remaining, currency)} still to collect`
                  : `Over by ${formatMoney(-remaining, currency)}`}
              </p>
            )}
          </div>

          {/* Submit */}
          <div className="mt-4 border-t border-slate-100 pt-3 dark:border-slate-800">
            <Button
              className="w-full"
              disabled={lines.length === 0 || !balanced}
              loading={saleMutation.isPending}
              onClick={completeSale}
            >
              {tenders.some((t) => t.method === 'deferred')
                ? 'Record partly unpaid sale'
                : 'Complete sale'}
            </Button>
          </div>

          {lastSale && (
            <p className="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-center text-xs text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
              Last sale <span className="font-mono">{lastSale.id}</span> ·{' '}
              {formatMoney(lastSale.total, lastSale.currency)}
            </p>
          )}
        </div>
      </div>
    </div>
  );
}

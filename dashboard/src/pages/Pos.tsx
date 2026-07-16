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
  Trash2,
} from 'lucide-react';
import {
  adminProductsService,
  getErrorMessage,
  ordersService,
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
  PosPaymentMethod,
  PosSaleInput,
} from '@/types';

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
  { value: 'creditCard', label: 'Card', icon: CreditCard },
  { value: 'deferred', label: 'Pay later', icon: Clock },
];

export function Pos() {
  const { t, locale } = useLocaleStore();
  const qc = useQueryClient();

  const [search, setSearch] = useState('');
  const [lines, setLines] = useState<CartLine[]>([]);
  const [payment, setPayment] = useState<PosPaymentMethod>('cash');
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

  const reset = () => {
    setLines([]);
    setPromo('');
    setCustomerName('');
    setCustomerPhone('');
    setUserId('');
    setPayment('cash');
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
    if (lines.length === 0) return;
    saleMutation.mutate({
      items: lines.map((l) => ({
        product_id: l.product.id,
        size: l.size,
        color_value: l.color ? hexArgbToInt(l.color) : 0,
        quantity: l.quantity,
      })),
      payment_method: payment,
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

          {/* Payment */}
          <div className="mt-3">
            <label className="label">Payment</label>
            <div className="grid grid-cols-3 gap-2">
              {PAYMENT_METHODS.map(({ value, label, icon: Icon }) => (
                <button
                  key={value}
                  type="button"
                  onClick={() => setPayment(value)}
                  className={`flex flex-col items-center gap-1 rounded-lg border px-2 py-2 text-xs font-medium transition ${
                    payment === value
                      ? 'border-brand-700 bg-brand-700 text-white'
                      : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'
                  }`}
                >
                  <Icon size={16} />
                  {label}
                </button>
              ))}
            </div>
          </div>

          {/* Total + submit */}
          <div className="mt-4 border-t border-slate-100 pt-3 dark:border-slate-800">
            <div className="mb-3 flex items-center justify-between">
              <span className="text-sm text-slate-500">
                Subtotal ({itemCount} item{itemCount === 1 ? '' : 's'})
              </span>
              <span className="text-lg font-bold">
                {formatMoney(subtotal, currency)}
              </span>
            </div>
            {promo.trim() !== '' && (
              <p className="mb-2 text-xs text-slate-400">
                Discount is applied by the server when the code is valid.
              </p>
            )}
            <Button
              className="w-full"
              disabled={lines.length === 0}
              loading={saleMutation.isPending}
              onClick={completeSale}
            >
              {payment === 'deferred' ? 'Record unpaid sale' : 'Complete sale'}
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

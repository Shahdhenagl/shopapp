import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Banknote, CheckCircle2, CreditCard } from 'lucide-react';
import { account, cartApi, catalog, checkout, getErrorMessage } from '@/api';
import { ErrorState, Loading } from '@/components/States';
import { useLocale } from '@/store/locale';
import { money } from '@/lib/format';
import { totalsFor } from '@/lib/totals';
import type { Order } from '@/types';

export function Checkout() {
  const navigate = useNavigate();
  const qc = useQueryClient();
  const t = useLocale((s) => s.t);

  const [address, setAddress] = useState({
    address: '',
    city: '',
    area: '',
    branch: '',
  });
  const [savedId, setSavedId] = useState<string>('');
  const [method, setMethod] = useState<'cash' | 'creditCard'>('cash');
  const [error, setError] = useState<string | null>(null);
  const [placed, setPlaced] = useState<Order | null>(null);

  const cartQuery = useQuery({ queryKey: ['cart'], queryFn: () => cartApi.get() });
  const settingsQuery = useQuery({
    queryKey: ['settings'],
    queryFn: () => catalog.settings(),
  });
  const addressesQuery = useQuery({
    queryKey: ['addresses'],
    queryFn: () => account.addresses(),
  });

  // Pick the default address once it arrives, so the common case is one tap.
  useEffect(() => {
    const saved = addressesQuery.data;
    if (!saved || saved.length === 0 || savedId) return;

    const preferred = saved.find((a) => a.is_default) ?? saved[0];
    setSavedId(preferred.id);
    setAddress({
      address: preferred.address,
      city: preferred.city ?? '',
      area: preferred.area ?? '',
      branch: preferred.branch ?? '',
    });
  }, [addressesQuery.data, savedId]);

  const chooseSaved = (id: string) => {
    setSavedId(id);
    const found = addressesQuery.data?.find((a) => a.id === id);
    if (!found) {
      setAddress({ address: '', city: '', area: '', branch: '' });
      return;
    }
    setAddress({
      address: found.address,
      city: found.city ?? '',
      area: found.area ?? '',
      branch: found.branch ?? '',
    });
  };

  const mutation = useMutation({
    mutationFn: () =>
      checkout.place({
        payment_method: method,
        address: {
          address: address.address.trim(),
          city: address.city.trim() || null,
          area: address.area.trim() || null,
          branch: address.branch.trim() || null,
        },
      }),
    onSuccess: (order) => {
      qc.invalidateQueries({ queryKey: ['cart'] });
      qc.invalidateQueries({ queryKey: ['orders'] });
      setPlaced(order);
    },
    onError: (e) => setError(getErrorMessage(e)),
  });

  if (cartQuery.isLoading) return <Loading />;
  if (cartQuery.error) {
    return (
      <ErrorState
        message={getErrorMessage(cartQuery.error)}
        onRetry={() => cartQuery.refetch()}
      />
    );
  }

  if (placed) {
    return (
      <div className="mx-auto max-w-sm py-16 text-center">
        <CheckCircle2 className="mx-auto mb-3 text-success" size={40} />
        <h1 className="text-title font-bold text-ink">{t('order_received')}</h1>
        <p className="mt-1 text-body text-muted">
          {t('order_number')}{' '}
          <span className="font-mono font-bold">{placed.id}</span>
        </p>
        <p className="price mt-1 text-title">
          {money(placed.total, placed.currency)}
        </p>
        <div className="mt-5 flex justify-center gap-2">
          <button className="btn btn--sm" onClick={() => navigate('/orders')}>
            {t('my_orders')}
          </button>
          <button
            className="btn btn--outlined btn--sm"
            onClick={() => navigate('/shop')}
          >
            {t('keep_shopping')}
          </button>
        </div>
      </div>
    );
  }

  const cart = cartQuery.data!;
  const flags = settingsQuery.data?.flags;
  const totals = totalsFor(cart, settingsQuery.data?.shipping_fee ?? 0);

  if (cart.items.length === 0) {
    return (
      <div className="py-16 text-center">
        <p className="text-body text-muted">{t('cart_empty')}</p>
        <button className="btn btn--sm mt-4" onClick={() => navigate('/shop')}>
          {t('browse_shop')}
        </button>
      </div>
    );
  }

  return (
    <div className="grid gap-5 lg:grid-cols-[1fr_320px]">
      <div>
        <h1 className="mb-3 text-title font-bold text-ink">{t('checkout')}</h1>

        <form
          className="card space-y-3 p-4"
          onSubmit={(e) => {
            e.preventDefault();
            setError(null);
            mutation.mutate();
          }}
          id="checkout-form"
        >
          <h2 className="text-body font-bold text-ink">
            {t('delivery_address')}
          </h2>

          {/* Saved addresses — the app picks the default and lets you switch. */}
          {(addressesQuery.data ?? []).length > 0 && (
            <select
              className="field"
              value={savedId}
              onChange={(e) => chooseSaved(e.target.value)}
            >
              {addressesQuery.data!.map((a) => (
                <option key={a.id} value={a.id}>
                  {a.label ? `${a.label} — ` : ''}
                  {a.address}
                  {a.is_default ? ` (${t('default_address')})` : ''}
                </option>
              ))}
              <option value="">＋ {t('add_address')}</option>
            </select>
          )}

          <div>
            <label className="label">{t('address')}</label>
            <input
              className="field"
              value={address.address}
              onChange={(e) =>
                setAddress((a) => ({ ...a, address: e.target.value }))
              }
              required
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="label">{t('city')}</label>
              <input
                className="field"
                value={address.city}
                onChange={(e) =>
                  setAddress((a) => ({ ...a, city: e.target.value }))
                }
              />
            </div>
            <div>
              <label className="label">{t('area')}</label>
              <input
                className="field"
                value={address.area}
                onChange={(e) =>
                  setAddress((a) => ({ ...a, area: e.target.value }))
                }
              />
            </div>
          </div>

          <h2 className="pt-2 text-body font-bold text-ink">
            {t('payment_method')}
          </h2>
          <div className="grid grid-cols-2 gap-2">
            {flags?.cash_payment !== false && (
              <button
                type="button"
                onClick={() => setMethod('cash')}
                className={`flex items-center justify-center gap-2 rounded-btn border p-3 text-body font-semibold ${
                  method === 'cash'
                    ? 'border-primary bg-primary text-on-primary'
                    : 'border-hairline text-ink'
                }`}
              >
                <Banknote size={16} /> {t('cash_on_delivery')}
              </button>
            )}
            {flags?.card_payment !== false && (
              <button
                type="button"
                onClick={() => setMethod('creditCard')}
                className={`flex items-center justify-center gap-2 rounded-btn border p-3 text-body font-semibold ${
                  method === 'creditCard'
                    ? 'border-primary bg-primary text-on-primary'
                    : 'border-hairline text-ink'
                }`}
              >
                <CreditCard size={16} /> {t('card')}
              </button>
            )}
          </div>

          {error && <p className="field-error">{error}</p>}
        </form>
      </div>

      <aside className="card h-fit p-4 lg:sticky lg:top-20">
        <h2 className="mb-3 text-body font-bold text-ink">{t('summary')}</h2>
        <dl className="space-y-2 text-body">
          <div className="flex justify-between text-muted">
            <dt>{t('subtotal')}</dt>
            <dd>{money(totals.subtotal, totals.currency)}</dd>
          </div>
          {totals.discount > 0 && (
            <div className="flex justify-between text-success">
              <dt>{t('discount')}</dt>
              <dd>− {money(totals.discount, totals.currency)}</dd>
            </div>
          )}
          {totals.shipping > 0 && (
            <div className="flex justify-between text-muted">
              <dt>{t('shipping')}</dt>
              <dd>{money(totals.shipping, totals.currency)}</dd>
            </div>
          )}
          <div className="flex justify-between pt-1 text-title font-bold text-ink">
            <dt>{t('total')}</dt>
            <dd>{money(totals.total, totals.currency)}</dd>
          </div>
        </dl>

        <button
          form="checkout-form"
          className="btn mt-4 w-full"
          disabled={mutation.isPending}
        >
          {mutation.isPending ? '…' : t('place_order')}
        </button>
      </aside>
    </div>
  );
}

import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';
import { cartApi, catalog, getErrorMessage } from '@/api';
import { Empty, ErrorState, Loading } from '@/components/States';
import { useAuth } from '@/store/auth';
import { useLocale } from '@/store/locale';
import { money, swatch } from '@/lib/format';
import { totalsFor } from '@/lib/totals';

export function CartPage() {
  const authed = useAuth((s) => Boolean(s.token));
  const t = useLocale((s) => s.t);
  const navigate = useNavigate();
  const qc = useQueryClient();
  const [promo, setPromo] = useState('');
  const [error, setError] = useState<string | null>(null);

  const query = useQuery({
    queryKey: ['cart'],
    queryFn: () => cartApi.get(),
    enabled: authed,
  });

  // Shipping isn't in the server's summary — the client adds it (as the app does).
  const settingsQuery = useQuery({
    queryKey: ['settings'],
    queryFn: () => catalog.settings(),
  });

  const refresh = (cart: unknown) => {
    qc.setQueryData(['cart'], cart);
    setError(null);
  };

  const qtyMutation = useMutation({
    mutationFn: ({ lineId, quantity }: { lineId: string; quantity: number }) =>
      quantity <= 0
        ? cartApi.remove(lineId)
        : cartApi.setQuantity(lineId, quantity),
    onSuccess: refresh,
    onError: (e) => setError(getErrorMessage(e)),
  });

  const promoMutation = useMutation({
    mutationFn: () => cartApi.applyPromo(promo.trim()),
    onSuccess: refresh,
    onError: (e) => setError(getErrorMessage(e)),
  });

  // The app shows the sign-in CTA on the empty cart for guests only.
  if (!authed) {
    return (
      <div className="py-16 text-center">
        <ShoppingBag className="mx-auto mb-3 text-hint" size={30} />
        <p className="text-body text-muted">{t('sign_in_to_cart')}</p>
        <Link to="/login" className="btn btn--sm mt-4">
          {t('sign_in')}
        </Link>
      </div>
    );
  }

  if (query.isLoading) return <Loading />;
  if (query.error) {
    return (
      <ErrorState
        message={getErrorMessage(query.error)}
        onRetry={() => query.refetch()}
      />
    );
  }

  const cart = query.data!;
  if (cart.items.length === 0) {
    return (
      <div className="py-16 text-center">
        <ShoppingBag className="mx-auto mb-3 text-hint" size={30} />
        <Empty label={t('cart_empty')} />
        <Link to="/shop" className="btn btn--sm">
          {t('browse_shop')}
        </Link>
      </div>
    );
  }

  const totals = totalsFor(cart, settingsQuery.data?.shipping_fee ?? 0);

  return (
    <div className="grid gap-5 lg:grid-cols-[1fr_320px]">
      <div>
        <h1 className="mb-3 text-title font-bold text-ink">{t('cart_title')}</h1>
        <ul className="space-y-3">
          {cart.items.map((item) => (
            <li key={item.line_id} className="card flex gap-3 p-3">
              <Link
                to={`/p/${item.product.id}`}
                className="h-24 w-20 flex-none overflow-hidden rounded-input bg-surface-variant"
              >
                {item.product.images[0] && (
                  <img
                    src={item.product.images[0]}
                    alt=""
                    className="h-full w-full object-cover"
                  />
                )}
              </Link>

              <div className="min-w-0 flex-1">
                <p className="truncate text-body font-semibold text-ink">
                  {item.product.name}
                </p>
                <div className="mt-1 flex items-center gap-2 text-caption text-muted">
                  {item.size && <span>{item.size}</span>}
                  {item.color > 0 && (
                    <span
                      className="h-3.5 w-3.5 rounded-pill border border-hairline"
                      style={{
                        background: swatch(
                          `#${item.color.toString(16).padStart(8, '0')}`,
                        ),
                      }}
                    />
                  )}
                </div>
                <p className="price mt-1">
                  {money(item.line_total, cart.summary.currency)}
                </p>
              </div>

              <div className="flex flex-col items-end justify-between">
                <button
                  onClick={() =>
                    qtyMutation.mutate({ lineId: item.line_id, quantity: 0 })
                  }
                  className="rounded-pill p-1.5 text-danger hover:bg-danger-surface"
                  aria-label={t("delete")}
                >
                  <Trash2 size={15} />
                </button>
                <div className="flex items-center gap-2 rounded-pill border border-hairline px-1.5 py-1">
                  <button
                    onClick={() =>
                      qtyMutation.mutate({
                        lineId: item.line_id,
                        quantity: item.quantity - 1,
                      })
                    }
                    className="rounded-pill p-1 text-ink"
                    aria-label="-"
                  >
                    <Minus size={13} />
                  </button>
                  <span className="w-5 text-center text-body">
                    {item.quantity}
                  </span>
                  <button
                    onClick={() =>
                      qtyMutation.mutate({
                        lineId: item.line_id,
                        quantity: item.quantity + 1,
                      })
                    }
                    className="rounded-pill p-1 text-ink"
                    aria-label="+"
                  >
                    <Plus size={13} />
                  </button>
                </div>
              </div>
            </li>
          ))}
        </ul>
      </div>

      {/* Summary */}
      <aside className="card h-fit p-4 lg:sticky lg:top-20">
        <h2 className="mb-3 text-body font-bold text-ink">{t('summary')}</h2>

        <div className="flex gap-2">
          <input
            className="field"
            placeholder={t('promo_code')}
            value={promo}
            onChange={(e) => setPromo(e.target.value.toUpperCase())}
          />
          <button
            className="btn btn--outlined btn--sm"
            disabled={!promo.trim() || promoMutation.isPending}
            onClick={() => promoMutation.mutate()}
          >
            {t('apply')}
          </button>
        </div>
        {error && <p className="field-error">{error}</p>}

        <hr className="my-3 border-0 border-t border-divider" />

        <dl className="space-y-2 text-body">
          <div className="flex justify-between text-muted">
            <dt>{t('subtotal')}</dt>
            <dd>{money(totals.subtotal, totals.currency)}</dd>
          </div>
          {totals.discount > 0 && (
            <div className="flex justify-between text-success">
              <dt>
                {t('discount')} {cart.summary.applied_promo?.code}
              </dt>
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

        <button className="btn mt-4 w-full" onClick={() => navigate('/checkout')}>
          {t('checkout')}
        </button>
      </aside>
    </div>
  );
}

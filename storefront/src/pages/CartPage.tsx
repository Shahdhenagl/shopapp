import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';
import { cartApi, getErrorMessage } from '@/api';
import { Empty, ErrorState, Loading } from '@/components/States';
import { useAuth } from '@/store/auth';
import { money, swatch } from '@/lib/format';

export function CartPage() {
  const authed = useAuth((s) => Boolean(s.token));
  const navigate = useNavigate();
  const qc = useQueryClient();
  const [promo, setPromo] = useState('');
  const [error, setError] = useState<string | null>(null);

  const query = useQuery({
    queryKey: ['cart'],
    queryFn: () => cartApi.get(),
    enabled: authed,
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

  if (!authed) {
    return (
      <div className="py-16 text-center">
        <ShoppingBag className="mx-auto mb-3 text-hint" size={30} />
        <p className="text-body text-muted">سجّل الدخول لعرض سلتك.</p>
        <Link to="/login" className="btn btn--sm mt-4">
          تسجيل الدخول
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
        <Empty label="سلتك فاضية." />
        <Link to="/shop" className="btn btn--sm">
          تصفّح المتجر
        </Link>
      </div>
    );
  }

  return (
    <div className="grid gap-5 lg:grid-cols-[1fr_320px]">
      <div>
        <h1 className="mb-3 text-title font-bold text-ink">سلة التسوّق</h1>
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
                  aria-label="حذف"
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
                    aria-label="إنقاص"
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
                    aria-label="زيادة"
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
        <h2 className="mb-3 text-body font-bold text-ink">الملخّص</h2>

        <div className="flex gap-2">
          <input
            className="field"
            placeholder="كود الخصم"
            value={promo}
            onChange={(e) => setPromo(e.target.value.toUpperCase())}
          />
          <button
            className="btn btn--outlined btn--sm"
            disabled={!promo.trim() || promoMutation.isPending}
            onClick={() => promoMutation.mutate()}
          >
            تطبيق
          </button>
        </div>
        {error && <p className="field-error">{error}</p>}

        <hr className="my-3 border-0 border-t border-divider" />

        <dl className="space-y-2 text-body">
          <div className="flex justify-between text-muted">
            <dt>المجموع الفرعي</dt>
            <dd>{money(cart.summary.subtotal, cart.summary.currency)}</dd>
          </div>
          {cart.summary.discount > 0 && (
            <div className="flex justify-between text-success">
              <dt>الخصم {cart.summary.applied_promo?.code}</dt>
              <dd>− {money(cart.summary.discount, cart.summary.currency)}</dd>
            </div>
          )}
          <div className="flex justify-between pt-1 text-title font-bold text-ink">
            <dt>الإجمالي</dt>
            <dd>{money(cart.summary.total, cart.summary.currency)}</dd>
          </div>
        </dl>

        <button className="btn mt-4 w-full" onClick={() => navigate('/checkout')}>
          إتمام الشراء
        </button>
      </aside>
    </div>
  );
}

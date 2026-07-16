import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Banknote, CheckCircle2, CreditCard } from 'lucide-react';
import { cartApi, catalog, checkout, getErrorMessage } from '@/api';
import { ErrorState, Loading } from '@/components/States';
import { money } from '@/lib/format';
import type { Order } from '@/types';

export function Checkout() {
  const navigate = useNavigate();
  const qc = useQueryClient();

  const [address, setAddress] = useState({
    address: '',
    city: '',
    area: '',
    branch: '',
  });
  const [method, setMethod] = useState<'cash' | 'creditCard'>('cash');
  const [error, setError] = useState<string | null>(null);
  const [placed, setPlaced] = useState<Order | null>(null);

  const cartQuery = useQuery({ queryKey: ['cart'], queryFn: () => cartApi.get() });
  const settingsQuery = useQuery({
    queryKey: ['settings'],
    queryFn: () => catalog.settings(),
  });

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
        <h1 className="text-title font-bold text-ink">تم استلام طلبك</h1>
        <p className="mt-1 text-body text-muted">
          رقم الطلب <span className="font-mono font-bold">{placed.id}</span>
        </p>
        <p className="price mt-1 text-title">
          {money(placed.total, placed.currency)}
        </p>
        <div className="mt-5 flex justify-center gap-2">
          <button className="btn btn--sm" onClick={() => navigate('/account')}>
            طلباتي
          </button>
          <button
            className="btn btn--outlined btn--sm"
            onClick={() => navigate('/shop')}
          >
            متابعة التسوّق
          </button>
        </div>
      </div>
    );
  }

  const cart = cartQuery.data!;
  const flags = settingsQuery.data?.flags;
  const shipping = settingsQuery.data?.shipping_fee ?? 0;

  if (cart.items.length === 0) {
    return (
      <div className="py-16 text-center">
        <p className="text-body text-muted">سلتك فاضية.</p>
        <button className="btn btn--sm mt-4" onClick={() => navigate('/shop')}>
          تصفّح المتجر
        </button>
      </div>
    );
  }

  return (
    <div className="grid gap-5 lg:grid-cols-[1fr_320px]">
      <div>
        <h1 className="mb-3 text-title font-bold text-ink">إتمام الشراء</h1>

        <form
          className="card space-y-3 p-4"
          onSubmit={(e) => {
            e.preventDefault();
            setError(null);
            mutation.mutate();
          }}
          id="checkout-form"
        >
          <h2 className="text-body font-bold text-ink">عنوان التوصيل</h2>

          <div>
            <label className="label">العنوان</label>
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
              <label className="label">المدينة</label>
              <input
                className="field"
                value={address.city}
                onChange={(e) =>
                  setAddress((a) => ({ ...a, city: e.target.value }))
                }
              />
            </div>
            <div>
              <label className="label">المنطقة</label>
              <input
                className="field"
                value={address.area}
                onChange={(e) =>
                  setAddress((a) => ({ ...a, area: e.target.value }))
                }
              />
            </div>
          </div>

          <h2 className="pt-2 text-body font-bold text-ink">طريقة الدفع</h2>
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
                <Banknote size={16} /> عند الاستلام
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
                <CreditCard size={16} /> بطاقة
              </button>
            )}
          </div>

          {error && <p className="field-error">{error}</p>}
        </form>
      </div>

      <aside className="card h-fit p-4 lg:sticky lg:top-20">
        <h2 className="mb-3 text-body font-bold text-ink">الملخّص</h2>
        <dl className="space-y-2 text-body">
          <div className="flex justify-between text-muted">
            <dt>المجموع الفرعي</dt>
            <dd>{money(cart.summary.subtotal, cart.summary.currency)}</dd>
          </div>
          {cart.summary.discount > 0 && (
            <div className="flex justify-between text-success">
              <dt>الخصم</dt>
              <dd>− {money(cart.summary.discount, cart.summary.currency)}</dd>
            </div>
          )}
          {shipping > 0 && (
            <div className="flex justify-between text-muted">
              <dt>الشحن</dt>
              <dd>{money(shipping, cart.summary.currency)}</dd>
            </div>
          )}
          <div className="flex justify-between pt-1 text-title font-bold text-ink">
            <dt>الإجمالي</dt>
            <dd>{money(cart.summary.total, cart.summary.currency)}</dd>
          </div>
        </dl>

        <button
          form="checkout-form"
          className="btn mt-4 w-full"
          disabled={mutation.isPending}
        >
          {mutation.isPending ? '…' : 'تأكيد الطلب'}
        </button>
      </aside>
    </div>
  );
}

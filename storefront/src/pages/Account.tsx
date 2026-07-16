import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { LogOut, Package } from 'lucide-react';
import { account, auth, getErrorMessage } from '@/api';
import { Empty, ErrorState, Loading } from '@/components/States';
import { useAuth } from '@/store/auth';
import { formatDate, money } from '@/lib/format';
import type { OrderStatus } from '@/types';

const STATUS_LABELS: Record<OrderStatus, string> = {
  pending: 'قيد الانتظار',
  paid: 'مدفوع',
  shipped: 'تم الشحن',
  delivered: 'تم التوصيل',
  cancelled: 'ملغي',
  refunded: 'مسترجع',
};

const statusChip = (status: OrderStatus) =>
  status === 'delivered' || status === 'paid'
    ? 'chip--success'
    : status === 'cancelled' || status === 'refunded'
      ? 'chip--error'
      : '';

export function Account() {
  const navigate = useNavigate();
  const { user, setUser, clear } = useAuth();

  const meQuery = useQuery({ queryKey: ['me'], queryFn: () => auth.me() });
  const ordersQuery = useQuery({
    queryKey: ['orders'],
    queryFn: () => account.orders(),
  });

  useEffect(() => {
    if (meQuery.data) setUser(meQuery.data);
  }, [meQuery.data, setUser]);

  const signOut = async () => {
    try {
      await auth.logout();
    } catch {
      // The local session goes either way.
    }
    clear();
    navigate('/');
  };

  const profile = meQuery.data ?? user;

  return (
    <div>
      <div className="card mb-5 flex items-center gap-3 p-4">
        {profile?.avatar_url ? (
          <img
            src={profile.avatar_url}
            alt=""
            className="h-12 w-12 rounded-pill object-cover"
          />
        ) : (
          <span className="flex h-12 w-12 items-center justify-center rounded-pill bg-surface-variant text-body font-bold text-muted">
            {profile?.name?.[0] ?? '؟'}
          </span>
        )}
        <div className="min-w-0 flex-1">
          <p className="truncate text-body font-bold text-ink">
            {profile?.name ?? '—'}
          </p>
          <p className="truncate text-caption text-muted">{profile?.email}</p>
        </div>
        <button
          onClick={signOut}
          className="flex items-center gap-1.5 rounded-pill px-3 py-2 text-caption font-semibold text-danger hover:bg-danger-surface"
        >
          <LogOut size={15} /> خروج
        </button>
      </div>

      <h2 className="mb-3 flex items-center gap-2 text-title font-bold text-ink">
        <Package size={18} /> طلباتي
      </h2>

      {ordersQuery.isLoading ? (
        <Loading />
      ) : ordersQuery.error ? (
        <ErrorState
          message={getErrorMessage(ordersQuery.error)}
          onRetry={() => ordersQuery.refetch()}
        />
      ) : (ordersQuery.data ?? []).length === 0 ? (
        <Empty label="لا توجد طلبات بعد." />
      ) : (
        <ul className="space-y-3">
          {ordersQuery.data!.map((order) => (
            <li key={order.id} className="card p-4">
              <div className="flex items-center justify-between">
                <span className="font-mono text-body font-bold text-ink">
                  {order.id}
                </span>
                <span className={`chip ${statusChip(order.status)}`}>
                  {STATUS_LABELS[order.status]}
                </span>
              </div>
              <p className="mt-1 text-caption text-muted">
                {formatDate(order.created_at)} · {order.items.length} صنف
              </p>
              <p className="price mt-1">{money(order.total, order.currency)}</p>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

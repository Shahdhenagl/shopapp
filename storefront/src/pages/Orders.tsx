import { useQuery } from '@tanstack/react-query';
import { account, getErrorMessage } from '@/api';
import { Empty, ErrorState, Loading } from '@/components/States';
import { useLocale } from '@/store/locale';
import { formatDate, money } from '@/lib/format';
import type { OrderStatus } from '@/types';

const CHIP: Partial<Record<OrderStatus, string>> = {
  paid: 'chip--success',
  delivered: 'chip--success',
  cancelled: 'chip--error',
  refunded: 'chip--error',
};

export function Orders() {
  const t = useLocale((s) => s.t);

  const query = useQuery({
    queryKey: ['orders'],
    queryFn: () => account.orders(),
  });

  return (
    <div className="mx-auto max-w-2xl">
      <h1 className="mb-4 text-title font-bold text-ink">{t('my_orders')}</h1>

      {query.isLoading ? (
        <Loading />
      ) : query.error ? (
        <ErrorState
          message={getErrorMessage(query.error)}
          onRetry={() => query.refetch()}
        />
      ) : (query.data ?? []).length === 0 ? (
        <Empty label={t('no_orders')} />
      ) : (
        <ul className="space-y-3">
          {query.data!.map((order) => (
            <li key={order.id} className="card p-4">
              <div className="flex items-center justify-between">
                <span className="font-mono text-body font-bold text-ink">
                  {order.id}
                </span>
                <span className={`chip ${CHIP[order.status] ?? ''}`}>
                  {t(`status_${order.status}`)}
                </span>
              </div>
              <p className="mt-1 text-caption text-muted">
                {formatDate(order.created_at)} · {order.items.length}
              </p>

              <ul className="mt-3 space-y-1 border-t border-divider pt-3">
                {order.items.map((item) => (
                  <li
                    key={item.id}
                    className="flex justify-between text-caption text-muted"
                  >
                    <span className="truncate">
                      {item.name_snapshot} × {item.quantity}
                    </span>
                    <span className="flex-none">
                      {money(item.line_total, order.currency)}
                    </span>
                  </li>
                ))}
              </ul>

              <p className="price mt-3 text-end">
                {money(order.total, order.currency)}
              </p>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

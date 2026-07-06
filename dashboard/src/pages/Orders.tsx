import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { X } from 'lucide-react';
import { getErrorMessage, ordersService } from '@/api';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/Badge';
import { DataTable, type Column } from '@/components/DataTable';
import { useLocaleStore } from '@/store/locale';
import { toast } from '@/store/toast';
import { formatMoney, formatDate } from '@/lib/format';
import {
  ORDER_STATUSES,
  orderStatusTone,
  paymentStatusTone,
  humanize,
} from '@/lib/status';
import type { Order, OrderStatus } from '@/types';

function OrderDrawer({
  order,
  onClose,
  onStatusChange,
  updating,
}: {
  order: Order;
  onClose: () => void;
  onStatusChange: (status: OrderStatus) => void;
  updating: boolean;
}) {
  return (
    <div className="fixed inset-0 z-50 flex justify-end bg-black/50">
      <div className="h-full w-full max-w-md overflow-y-auto bg-white shadow-xl dark:bg-slate-900">
        <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
          <div>
            <h2 className="font-semibold">Order {order.id}</h2>
            <p className="text-xs text-slate-400">{formatDate(order.created_at)}</p>
          </div>
          <button
            onClick={onClose}
            className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
          >
            <X size={20} />
          </button>
        </div>

        <div className="space-y-5 p-5">
          <div className="flex flex-wrap gap-2">
            <Badge tone={orderStatusTone(order.status)}>
              {humanize(order.status)}
            </Badge>
            <Badge tone={paymentStatusTone(order.payment_status)}>
              {humanize(order.payment_status)}
            </Badge>
          </div>

          <div>
            <p className="mb-1 text-xs uppercase tracking-wide text-slate-400">
              Customer
            </p>
            <p className="text-sm font-medium">{order.user_name ?? order.user_id}</p>
            {order.shipping_address && (
              <p className="text-sm text-slate-500">{order.shipping_address}</p>
            )}
          </div>

          <div>
            <p className="mb-2 text-xs uppercase tracking-wide text-slate-400">
              Items
            </p>
            <div className="space-y-2">
              {order.items.map((it) => (
                <div
                  key={it.id}
                  className="flex items-center justify-between rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-800"
                >
                  <div>
                    <p className="font-medium">{it.name_snapshot}</p>
                    <p className="text-xs text-slate-400">
                      Size {it.size} · Qty {it.quantity}
                    </p>
                  </div>
                  <span>{formatMoney(it.line_total, order.currency)}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="space-y-1 rounded-lg bg-slate-50 p-4 text-sm dark:bg-slate-800/50">
            <div className="flex justify-between">
              <span className="text-slate-500">Subtotal</span>
              <span>{formatMoney(order.subtotal, order.currency)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-500">
                Discount {order.promo_code ? `(${order.promo_code})` : ''}
              </span>
              <span>-{formatMoney(order.discount, order.currency)}</span>
            </div>
            <div className="flex justify-between border-t border-slate-200 pt-2 font-semibold dark:border-slate-700">
              <span>Total</span>
              <span>{formatMoney(order.total, order.currency)}</span>
            </div>
          </div>

          <div>
            <label className="label">Update status</label>
            <select
              className="input"
              value={order.status}
              disabled={updating}
              onChange={(e) => onStatusChange(e.target.value as OrderStatus)}
            >
              {ORDER_STATUSES.map((s) => (
                <option key={s} value={s}>
                  {humanize(s)}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>
    </div>
  );
}

export function Orders() {
  const t = useLocaleStore((s) => s.t);
  const qc = useQueryClient();
  const [statusFilter, setStatusFilter] = useState<OrderStatus | ''>('');
  const [selected, setSelected] = useState<Order | null>(null);

  const query = useQuery({
    queryKey: ['orders', statusFilter],
    queryFn: () => ordersService.list(statusFilter || undefined),
  });

  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: string; status: OrderStatus }) =>
      ordersService.updateStatus(id, status),
    onSuccess: (updated) => {
      qc.invalidateQueries({ queryKey: ['orders'] });
      qc.invalidateQueries({ queryKey: ['dashboard-stats'] });
      setSelected(updated);
      toast.success('Order updated');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const columns: Column<Order>[] = [
    { key: 'id', header: '#', render: (o) => <span className="font-mono">{o.id}</span> },
    { key: 'user', header: t('nav_users'), render: (o) => o.user_name ?? o.user_id },
    {
      key: 'total',
      header: 'Total',
      render: (o) => formatMoney(o.total, o.currency),
    },
    {
      key: 'payment',
      header: 'Payment',
      render: (o) => (
        <Badge tone={paymentStatusTone(o.payment_status)}>
          {humanize(o.payment_status)}
        </Badge>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (o) => (
        <Badge tone={orderStatusTone(o.status)}>{humanize(o.status)}</Badge>
      ),
    },
    { key: 'date', header: 'Date', render: (o) => formatDate(o.created_at) },
  ];

  return (
    <div>
      <PageHeader
        title={t('nav_orders')}
        actions={
          <select
            className="input max-w-[200px]"
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value as OrderStatus | '')}
          >
            <option value="">All statuses</option>
            {ORDER_STATUSES.map((s) => (
              <option key={s} value={s}>
                {humanize(s)}
              </option>
            ))}
          </select>
        }
      />

      <div className="card p-2">
        <DataTable
          columns={columns}
          rows={query.data ?? []}
          rowKey={(o) => o.id}
          loading={query.isLoading}
          error={query.error ? getErrorMessage(query.error) : null}
          onRetry={() => query.refetch()}
          onRowClick={(o) => setSelected(o)}
        />
      </div>

      {selected && (
        <OrderDrawer
          order={selected}
          updating={statusMutation.isPending}
          onClose={() => setSelected(null)}
          onStatusChange={(status) =>
            statusMutation.mutate({ id: selected.id, status })
          }
        />
      )}
    </div>
  );
}

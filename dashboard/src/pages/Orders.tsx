import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  CreditCard,
  Mail,
  MapPin,
  Package,
  Phone,
  Search,
  User,
  X,
} from 'lucide-react';
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

/** ARGB int → a CSS #RRGGBB colour (alpha dropped). */
function cssColor(argb: number): string {
  return '#' + (argb & 0xffffff).toString(16).padStart(6, '0');
}

function InfoRow({
  icon,
  children,
}: {
  icon: React.ReactNode;
  children: React.ReactNode;
}) {
  return (
    <div className="flex items-center gap-2.5 text-sm">
      <span className="text-slate-400">{icon}</span>
      <span className="min-w-0 truncate">{children}</span>
    </div>
  );
}

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
  const itemCount = order.items.reduce((n, it) => n + it.quantity, 0);

  return (
    <div className="fixed inset-0 z-50 flex justify-end bg-black/50" onClick={onClose}>
      <div
        className="flex h-full w-full max-w-lg flex-col bg-slate-50 shadow-xl dark:bg-slate-950"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center gap-3">
            <h2 className="font-mono text-lg font-bold">{order.id}</h2>
            <Badge tone={orderStatusTone(order.status)}>
              {humanize(order.status)}
            </Badge>
          </div>
          <button
            onClick={onClose}
            className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
          >
            <X size={20} />
          </button>
        </div>

        <div className="flex-1 space-y-4 overflow-y-auto p-5">
          {/* Meta strip */}
          <div className="flex flex-wrap items-center gap-2 text-xs text-slate-400">
            <span>{formatDate(order.created_at)}</span>
            <span>·</span>
            <Badge tone={paymentStatusTone(order.payment_status)}>
              {humanize(order.payment_status)}
            </Badge>
          </div>

          {/* Customer */}
          <div className="card space-y-2 p-4">
            <InfoRow icon={<User size={15} />}>
              <span className="font-medium">
                {order.user_name ??
                  order.customer_name ??
                  (order.user_id ? `User ${order.user_id}` : 'Walk-in customer')}
              </span>
              {order.channel === 'pos' && (
                <span className="ms-2 align-middle">
                  <Badge tone="purple">POS</Badge>
                </span>
              )}
            </InfoRow>
            {order.customer_phone && (
              <InfoRow icon={<Phone size={15} />}>{order.customer_phone}</InfoRow>
            )}
            {order.user_email && (
              <InfoRow icon={<Mail size={15} />}>{order.user_email}</InfoRow>
            )}
            {order.shipping_address && (
              <InfoRow icon={<MapPin size={15} />}>
                {order.shipping_address}
              </InfoRow>
            )}
            <InfoRow icon={<CreditCard size={15} />}>
              {humanize(order.payment_method ?? '—')}
            </InfoRow>
          </div>

          {/* Items */}
          <div>
            <p className="mb-2 px-1 text-xs font-medium uppercase tracking-wide text-slate-400">
              {order.items.length} product(s) · {itemCount} item(s)
            </p>
            <div className="card divide-y divide-slate-100 p-0 dark:divide-slate-800">
              {order.items.map((it) => (
                <div key={it.id} className="flex items-center gap-3 p-3">
                  {/* Thumbnail */}
                  {it.image ? (
                    <img
                      src={it.image}
                      alt=""
                      className="h-14 w-14 flex-shrink-0 rounded-lg border border-slate-200 object-cover dark:border-slate-700"
                    />
                  ) : (
                    <div className="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-100 text-slate-300 dark:border-slate-700 dark:bg-slate-800">
                      <Package size={20} />
                    </div>
                  )}

                  {/* Details */}
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium">
                      {it.name_snapshot}
                    </p>
                    <div className="mt-1 flex items-center gap-2 text-xs text-slate-400">
                      {it.color_value > 0 && (
                        <span
                          className="inline-block h-3.5 w-3.5 rounded-full border border-slate-300 dark:border-slate-600"
                          style={{ backgroundColor: cssColor(it.color_value) }}
                          title={it.color ?? undefined}
                        />
                      )}
                      {it.size && <span>Size {it.size}</span>}
                      <span>×{it.quantity}</span>
                    </div>
                  </div>

                  {/* Price */}
                  <div className="flex-shrink-0 text-right text-sm">
                    <p className="font-medium">
                      {formatMoney(it.line_total, order.currency)}
                    </p>
                    <p className="text-xs text-slate-400">
                      {formatMoney(it.unit_price, order.currency)} ea
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Totals */}
          <div className="card space-y-1.5 p-4 text-sm">
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
            <div className="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold dark:border-slate-700">
              <span>Total</span>
              <span>{formatMoney(order.total, order.currency)}</span>
            </div>
          </div>
        </div>

        {/* Sticky status footer */}
        <div className="border-t border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
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
  );
}

export function Orders() {
  const t = useLocaleStore((s) => s.t);
  const qc = useQueryClient();
  const [statusFilter, setStatusFilter] = useState<OrderStatus | ''>('');
  const [search, setSearch] = useState('');
  const [selected, setSelected] = useState<Order | null>(null);

  const query = useQuery({
    queryKey: ['orders', statusFilter],
    queryFn: () => ordersService.list(statusFilter || undefined),
  });

  // Client-side search over order id, customer name/email and phone.
  const rows = useMemo(() => {
    const q = search.trim().toLowerCase();
    const all = query.data ?? [];
    if (!q) return all;
    return all.filter((o) =>
      [o.id, o.user_name, o.user_email, o.user_id]
        .filter(Boolean)
        .some((v) => String(v).toLowerCase().includes(q)),
    );
  }, [query.data, search]);

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
      <PageHeader title={t('nav_orders')} />

      <div className="card mb-4 flex flex-wrap items-center gap-3 p-4">
        <div className="relative min-w-[200px] flex-1">
          <Search
            size={16}
            className="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-slate-400"
          />
          <input
            className="input ps-9"
            placeholder={t('search')}
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        <select
          className="input max-w-[220px]"
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
      </div>

      <div className="card p-2">
        <DataTable
          columns={columns}
          rows={rows}
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

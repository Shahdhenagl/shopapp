import { useQuery } from '@tanstack/react-query';
import {
  Area,
  AreaChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';
import { Package, ShoppingCart, Users, Wallet } from 'lucide-react';
import { dashboardService, getErrorMessage } from '@/api';
import { StatCard } from '@/components/StatCard';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/Badge';
import { DataTable, type Column } from '@/components/DataTable';
import { LoadingState, ErrorState } from '@/components/States';
import { useLocaleStore } from '@/store/locale';
import { formatMoney, formatDate } from '@/lib/format';
import { orderStatusTone, humanize } from '@/lib/status';
import type { Order } from '@/types';

export function Dashboard() {
  const t = useLocaleStore((s) => s.t);
  const { data, isLoading, error, refetch } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: () => dashboardService.stats(),
  });

  if (isLoading) return <LoadingState />;
  if (error || !data)
    return <ErrorState message={getErrorMessage(error)} onRetry={() => refetch()} />;

  const orderColumns: Column<Order>[] = [
    { key: 'id', header: '#', render: (o) => <span className="font-mono">{o.id}</span> },
    { key: 'user', header: t('nav_users'), render: (o) => o.user_name ?? o.user_id },
    {
      key: 'total',
      header: t('total_revenue'),
      render: (o) => formatMoney(o.total, o.currency),
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
      <PageHeader title={t('nav_dashboard')} subtitle="MODIST store overview" />

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label={t('total_products')} value={data.products} icon={Package} tone="brand" />
        <StatCard label={t('total_orders')} value={data.orders} icon={ShoppingCart} tone="orange" />
        <StatCard
          label={t('total_revenue')}
          value={formatMoney(data.revenue, data.currency)}
          icon={Wallet}
          tone="green"
        />
        <StatCard label={t('total_users')} value={data.users} icon={Users} tone="purple" />
      </div>

      <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div className="card p-5 lg:col-span-2">
          <h3 className="mb-4 font-semibold">{t('sales_overview')}</h3>
          <ResponsiveContainer width="100%" height={280}>
            <AreaChart data={data.salesByDay} margin={{ left: -10, right: 10 }}>
              <defs>
                <linearGradient id="rev" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#2b4574" stopOpacity={0.4} />
                  <stop offset="95%" stopColor="#2b4574" stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" strokeOpacity={0.4} />
              <XAxis dataKey="day" fontSize={12} stroke="#94a3b8" />
              <YAxis fontSize={12} stroke="#94a3b8" />
              <Tooltip
                contentStyle={{
                  borderRadius: 8,
                  border: '1px solid #e2e8f0',
                  fontSize: 12,
                }}
              />
              <Area
                type="monotone"
                dataKey="revenue"
                stroke="#2b4574"
                strokeWidth={2}
                fill="url(#rev)"
                name={t('total_revenue')}
              />
            </AreaChart>
          </ResponsiveContainer>
        </div>

        <div className="card p-5">
          <h3 className="mb-4 font-semibold">Orders / day</h3>
          <ResponsiveContainer width="100%" height={280}>
            <AreaChart data={data.salesByDay} margin={{ left: -20, right: 10 }}>
              <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" strokeOpacity={0.4} />
              <XAxis dataKey="day" fontSize={12} stroke="#94a3b8" />
              <YAxis fontSize={12} stroke="#94a3b8" allowDecimals={false} />
              <Tooltip contentStyle={{ borderRadius: 8, fontSize: 12 }} />
              <Area
                type="monotone"
                dataKey="orders"
                stroke="#7b1e1e"
                strokeWidth={2}
                fill="#7b1e1e"
                fillOpacity={0.15}
                name={t('total_orders')}
              />
            </AreaChart>
          </ResponsiveContainer>
        </div>
      </div>

      <div className="card mt-6 p-5">
        <h3 className="mb-4 font-semibold">{t('recent_orders')}</h3>
        <DataTable
          columns={orderColumns}
          rows={data.recentOrders}
          rowKey={(o) => o.id}
        />
      </div>
    </div>
  );
}

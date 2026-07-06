import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Mail, Phone, User as UserIcon } from 'lucide-react';
import { getErrorMessage, usersService } from '@/api';
import { PageHeader } from '@/components/PageHeader';
import { Modal } from '@/components/Modal';
import { DataTable, type Column } from '@/components/DataTable';
import { useLocaleStore } from '@/store/locale';
import type { User } from '@/types';

export function Users() {
  const t = useLocaleStore((s) => s.t);
  const [selected, setSelected] = useState<User | null>(null);

  const query = useQuery({
    queryKey: ['users'],
    queryFn: () => usersService.list(),
  });

  const columns: Column<User>[] = [
    { key: 'id', header: '#', render: (u) => <span className="font-mono">{u.id}</span> },
    {
      key: 'name',
      header: 'Name',
      render: (u) => (
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
            {u.name.charAt(0)}
          </div>
          <span className="font-medium">{u.name}</span>
        </div>
      ),
    },
    { key: 'email', header: 'Email', render: (u) => u.email },
    { key: 'phone', header: 'Phone', render: (u) => u.phone ?? '—' },
  ];

  return (
    <div>
      <PageHeader title={t('nav_users')} subtitle={`${query.data?.length ?? 0} user(s)`} />

      <div className="card p-2">
        <DataTable
          columns={columns}
          rows={query.data ?? []}
          rowKey={(u) => u.id}
          loading={query.isLoading}
          error={query.error ? getErrorMessage(query.error) : null}
          onRetry={() => query.refetch()}
          onRowClick={(u) => setSelected(u)}
        />
      </div>

      <Modal
        open={!!selected}
        title="User details"
        onClose={() => setSelected(null)}
        size="md"
      >
        {selected && (
          <div className="space-y-4">
            <div className="flex items-center gap-4">
              <div className="flex h-16 w-16 items-center justify-center rounded-full bg-brand-700 text-2xl font-bold text-white">
                {selected.name.charAt(0)}
              </div>
              <div>
                <p className="text-lg font-semibold">{selected.name}</p>
                <p className="text-sm text-slate-400">ID {selected.id}</p>
              </div>
            </div>
            <div className="space-y-2 text-sm">
              <div className="flex items-center gap-3">
                <Mail size={16} className="text-slate-400" />
                {selected.email}
              </div>
              <div className="flex items-center gap-3">
                <Phone size={16} className="text-slate-400" />
                {selected.phone ?? '—'}
              </div>
              <div className="flex items-center gap-3">
                <UserIcon size={16} className="text-slate-400" />
                {selected.avatar_url ?? 'No avatar'}
              </div>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Mail, Phone, Plus, User as UserIcon } from 'lucide-react';
import { getErrorMessage, usersService } from '@/api';
import type { CustomerInput } from '@/api/users';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/Button';
import { Modal } from '@/components/Modal';
import { DataTable, type Column } from '@/components/DataTable';
import { useLocaleStore } from '@/store/locale';
import { toast } from '@/store/toast';
import type { User } from '@/types';

function CustomerForm({
  submitting,
  onSubmit,
  onCancel,
}: {
  submitting?: boolean;
  onSubmit: (v: CustomerInput) => void;
  onCancel: () => void;
}) {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<CustomerInput>({
    defaultValues: { name: '', email: '', phone: '', password: '' },
  });

  return (
    <form
      onSubmit={handleSubmit((v) =>
        onSubmit({ ...v, phone: v.phone?.trim() || null }),
      )}
      className="space-y-4"
    >
      <div>
        <label className="label">Name</label>
        <input className="input" {...register('name', { required: 'Required' })} />
        {errors.name && <p className="field-error">{errors.name.message}</p>}
      </div>
      <div>
        <label className="label">Email</label>
        <input
          type="email"
          className="input"
          {...register('email', { required: 'Required' })}
        />
        {errors.email && <p className="field-error">{errors.email.message}</p>}
      </div>
      <div>
        <label className="label">Phone (optional)</label>
        <input className="input" placeholder="+20…" {...register('phone')} />
      </div>
      <div>
        <label className="label">Password</label>
        <input
          type="text"
          className="input font-mono"
          {...register('password', {
            required: 'Required',
            minLength: { value: 8, message: 'At least 8 characters' },
          })}
        />
        {errors.password && (
          <p className="field-error">{errors.password.message}</p>
        )}
        <p className="mt-1 text-xs text-slate-400">
          Share it with the customer, or they can reset it later. The account is
          created email-verified.
        </p>
      </div>

      <div className="flex justify-end gap-2 pt-2">
        <Button type="button" variant="secondary" onClick={onCancel}>
          Cancel
        </Button>
        <Button type="submit" loading={submitting}>
          Create
        </Button>
      </div>
    </form>
  );
}

export function Users() {
  const t = useLocaleStore((s) => s.t);
  const qc = useQueryClient();
  const [selected, setSelected] = useState<User | null>(null);
  const [creating, setCreating] = useState(false);

  const query = useQuery({
    queryKey: ['users'],
    queryFn: () => usersService.list(),
  });

  const createMutation = useMutation({
    mutationFn: (input: CustomerInput) => usersService.create(input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['users'] });
      setCreating(false);
      toast.success('Customer created');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
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
      <PageHeader
        title={t('nav_users')}
        subtitle={`${query.data?.length ?? 0} user(s)`}
        actions={
          <Button icon={<Plus size={16} />} onClick={() => setCreating(true)}>
            {t('create')}
          </Button>
        }
      />

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
        open={creating}
        title="New customer"
        onClose={() => setCreating(false)}
        size="md"
      >
        <CustomerForm
          submitting={createMutation.isPending}
          onSubmit={(v) => createMutation.mutate(v)}
          onCancel={() => setCreating(false)}
        />
      </Modal>

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

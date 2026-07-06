import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { getErrorMessage, promosService } from '@/api';
import type { PromoInput } from '@/api/promos';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/Button';
import { Badge } from '@/components/Badge';
import { Modal } from '@/components/Modal';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { DataTable, type Column } from '@/components/DataTable';
import { useLocaleStore } from '@/store/locale';
import { toast } from '@/store/toast';
import { formatDate } from '@/lib/format';
import type { PromoCode, PromoType } from '@/types';

interface FormValues {
  code: string;
  type: PromoType;
  fraction: number;
  active: boolean;
  starts_at: string;
  ends_at: string;
  usage_limit: number | '';
}

function PromoForm({
  initial,
  submitting,
  onSubmit,
  onCancel,
}: {
  initial?: PromoCode;
  submitting?: boolean;
  onSubmit: (v: PromoInput) => void;
  onCancel: () => void;
}) {
  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm<FormValues>({
    defaultValues: {
      code: initial?.code ?? '',
      type: initial?.type ?? 'percent',
      fraction: initial?.fraction ?? 0.1,
      active: initial?.active ?? true,
      starts_at: initial?.starts_at?.slice(0, 10) ?? '',
      ends_at: initial?.ends_at?.slice(0, 10) ?? '',
      usage_limit: initial?.usage_limit ?? '',
    },
  });

  const type = watch('type');

  return (
    <form
      onSubmit={handleSubmit((v) =>
        onSubmit({
          code: v.code,
          type: v.type,
          fraction: Number(v.fraction),
          active: v.active,
          starts_at: v.starts_at || null,
          ends_at: v.ends_at || null,
          usage_limit: v.usage_limit === '' ? null : Number(v.usage_limit),
        }),
      )}
      className="space-y-4"
    >
      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className="label">Code</label>
          <input
            className="input uppercase"
            {...register('code', { required: 'Required' })}
          />
          {errors.code && <p className="field-error">{errors.code.message}</p>}
        </div>
        <div>
          <label className="label">Type</label>
          <select className="input" {...register('type')}>
            <option value="percent">Percent</option>
            <option value="fixed">Fixed</option>
          </select>
        </div>
      </div>

      <div>
        <label className="label">
          {type === 'percent' ? 'Fraction (0.10 = 10%)' : 'Amount'}
        </label>
        <input
          type="number"
          step="0.01"
          className="input"
          {...register('fraction', {
            required: 'Required',
            min: { value: 0, message: 'Must be ≥ 0' },
          })}
        />
        {errors.fraction && <p className="field-error">{errors.fraction.message}</p>}
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className="label">Starts at</label>
          <input type="date" className="input" {...register('starts_at')} />
        </div>
        <div>
          <label className="label">Ends at</label>
          <input type="date" className="input" {...register('ends_at')} />
        </div>
      </div>

      <div>
        <label className="label">Usage limit (blank = unlimited)</label>
        <input type="number" className="input" {...register('usage_limit')} />
      </div>

      <label className="flex items-center gap-2 text-sm">
        <input type="checkbox" className="h-4 w-4 rounded" {...register('active')} />
        Active
      </label>

      <div className="flex justify-end gap-2 pt-2">
        <Button type="button" variant="secondary" onClick={onCancel}>
          Cancel
        </Button>
        <Button type="submit" loading={submitting}>
          Save
        </Button>
      </div>
    </form>
  );
}

export function Promos() {
  const t = useLocaleStore((s) => s.t);
  const qc = useQueryClient();
  const [creating, setCreating] = useState(false);
  const [editing, setEditing] = useState<PromoCode | null>(null);
  const [deleting, setDeleting] = useState<PromoCode | null>(null);

  const query = useQuery({
    queryKey: ['promos'],
    queryFn: () => promosService.list(),
  });

  const createMutation = useMutation({
    mutationFn: (input: PromoInput) => promosService.create(input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['promos'] });
      setCreating(false);
      toast.success('Promo created');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: string; input: PromoInput }) =>
      promosService.update(id, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['promos'] });
      setEditing(null);
      toast.success('Promo updated');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const toggleMutation = useMutation({
    mutationFn: ({ id, active }: { id: string; active: boolean }) =>
      promosService.toggleActive(id, active),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['promos'] }),
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: string) => promosService.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['promos'] });
      setDeleting(null);
      toast.success('Promo deleted');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const columns: Column<PromoCode>[] = [
    { key: 'code', header: 'Code', render: (p) => <span className="font-mono font-semibold">{p.code}</span> },
    { key: 'type', header: 'Type', render: (p) => humanizeType(p) },
    {
      key: 'value',
      header: 'Value',
      render: (p) =>
        p.type === 'percent' ? `${Math.round(p.fraction * 100)}%` : p.fraction,
    },
    {
      key: 'usage',
      header: 'Usage',
      render: (p) =>
        `${p.used_count}${p.usage_limit ? ` / ${p.usage_limit}` : ''}`,
    },
    {
      key: 'window',
      header: 'Window',
      render: (p) =>
        p.starts_at || p.ends_at
          ? `${p.starts_at ? formatDate(p.starts_at) : '…'} → ${
              p.ends_at ? formatDate(p.ends_at) : '…'
            }`
          : '—',
    },
    {
      key: 'active',
      header: 'Active',
      render: (p) => (
        <button
          onClick={() => toggleMutation.mutate({ id: p.id, active: !p.active })}
          className="cursor-pointer"
        >
          <Badge tone={p.active ? 'green' : 'gray'}>
            {p.active ? t('active') : t('inactive')}
          </Badge>
        </button>
      ),
    },
    {
      key: 'actions',
      header: '',
      className: 'text-end',
      render: (p) => (
        <div className="flex justify-end gap-1">
          <Button variant="ghost" size="sm" onClick={() => setEditing(p)}>
            <Pencil size={16} />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            className="text-red-500"
            onClick={() => setDeleting(p)}
          >
            <Trash2 size={16} />
          </Button>
        </div>
      ),
    },
  ];

  return (
    <div>
      <PageHeader
        title={t('nav_promos')}
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
          rowKey={(p) => p.id}
          loading={query.isLoading}
          error={query.error ? getErrorMessage(query.error) : null}
          onRetry={() => query.refetch()}
        />
      </div>

      <Modal open={creating} title="New promo" onClose={() => setCreating(false)} size="md">
        <PromoForm
          submitting={createMutation.isPending}
          onSubmit={(v) => createMutation.mutate(v)}
          onCancel={() => setCreating(false)}
        />
      </Modal>

      <Modal open={!!editing} title="Edit promo" onClose={() => setEditing(null)} size="md">
        {editing && (
          <PromoForm
            initial={editing}
            submitting={updateMutation.isPending}
            onSubmit={(v) => updateMutation.mutate({ id: editing.id, input: v })}
            onCancel={() => setEditing(null)}
          />
        )}
      </Modal>

      <ConfirmDialog
        open={!!deleting}
        title="Delete promo"
        message={`Delete "${deleting?.code}"?`}
        loading={deleteMutation.isPending}
        onConfirm={() => deleting && deleteMutation.mutate(deleting.id)}
        onCancel={() => setDeleting(null)}
      />
    </div>
  );
}

function humanizeType(p: PromoCode): string {
  return p.type === 'percent' ? 'Percent' : 'Fixed';
}

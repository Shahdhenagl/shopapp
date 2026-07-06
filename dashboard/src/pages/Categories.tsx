import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { categoriesService, getErrorMessage } from '@/api';
import type { CategoryInput } from '@/api/categories';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/Button';
import { Modal } from '@/components/Modal';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { DataTable, type Column } from '@/components/DataTable';
import { useLocaleStore } from '@/store/locale';
import { toast } from '@/store/toast';
import type { Category } from '@/types';

interface FormValues {
  id: string;
  label_key: string;
  icon_key: string;
  sort_order: number;
}

function CategoryForm({
  initial,
  submitting,
  onSubmit,
  onCancel,
}: {
  initial?: Category;
  submitting?: boolean;
  onSubmit: (v: CategoryInput) => void;
  onCancel: () => void;
}) {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    defaultValues: {
      id: initial?.id ?? '',
      label_key: initial?.label_key ?? '',
      icon_key: initial?.icon_key ?? '',
      sort_order: initial?.sort_order ?? 0,
    },
  });

  return (
    <form
      onSubmit={handleSubmit((v) =>
        onSubmit({
          id: v.id.trim().toLowerCase(),
          label_key: v.label_key.trim(),
          icon_key: v.icon_key.trim(),
          sort_order: Number(v.sort_order),
        }),
      )}
      className="space-y-4"
    >
      <div>
        <label className="label">Slug (id)</label>
        <input
          className="input"
          disabled={!!initial}
          {...register('id', { required: 'Slug is required' })}
        />
        {errors.id && <p className="field-error">{errors.id.message}</p>}
      </div>
      <div>
        <label className="label">Label key</label>
        <input
          className="input"
          placeholder="category_tshirt"
          {...register('label_key', { required: 'Required' })}
        />
        {errors.label_key && <p className="field-error">{errors.label_key.message}</p>}
      </div>
      <div>
        <label className="label">Icon key</label>
        <input
          className="input"
          placeholder="tshirt"
          {...register('icon_key', { required: 'Required' })}
        />
        {errors.icon_key && <p className="field-error">{errors.icon_key.message}</p>}
      </div>
      <div>
        <label className="label">Sort order</label>
        <input type="number" className="input" {...register('sort_order')} />
      </div>
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

export function Categories() {
  const t = useLocaleStore((s) => s.t);
  const qc = useQueryClient();
  const [creating, setCreating] = useState(false);
  const [editing, setEditing] = useState<Category | null>(null);
  const [deleting, setDeleting] = useState<Category | null>(null);

  const query = useQuery({
    queryKey: ['categories'],
    queryFn: () => categoriesService.list(),
  });

  const createMutation = useMutation({
    mutationFn: (input: CategoryInput) => categoriesService.create(input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['categories'] });
      setCreating(false);
      toast.success('Category created');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: string; input: CategoryInput }) =>
      categoriesService.update(id, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['categories'] });
      setEditing(null);
      toast.success('Category updated');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: string) => categoriesService.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['categories'] });
      setDeleting(null);
      toast.success('Category deleted');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const columns: Column<Category>[] = [
    { key: 'id', header: 'Slug', render: (c) => <span className="font-mono">{c.id}</span> },
    { key: 'label', header: 'Label key', render: (c) => c.label_key },
    { key: 'icon', header: 'Icon key', render: (c) => c.icon_key },
    { key: 'sort', header: 'Sort', render: (c) => c.sort_order ?? 0 },
    {
      key: 'actions',
      header: '',
      className: 'text-end',
      render: (c) => (
        <div className="flex justify-end gap-1">
          <Button variant="ghost" size="sm" onClick={() => setEditing(c)}>
            <Pencil size={16} />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            className="text-red-500"
            onClick={() => setDeleting(c)}
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
        title={t('nav_categories')}
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
          rowKey={(c) => c.id}
          loading={query.isLoading}
          error={query.error ? getErrorMessage(query.error) : null}
          onRetry={() => query.refetch()}
        />
      </div>

      <Modal open={creating} title="New category" onClose={() => setCreating(false)} size="md">
        <CategoryForm
          submitting={createMutation.isPending}
          onSubmit={(v) => createMutation.mutate(v)}
          onCancel={() => setCreating(false)}
        />
      </Modal>

      <Modal open={!!editing} title="Edit category" onClose={() => setEditing(null)} size="md">
        {editing && (
          <CategoryForm
            initial={editing}
            submitting={updateMutation.isPending}
            onSubmit={(v) => updateMutation.mutate({ id: editing.id, input: v })}
            onCancel={() => setEditing(null)}
          />
        )}
      </Modal>

      <ConfirmDialog
        open={!!deleting}
        title="Delete category"
        message={`Delete "${deleting?.id}"?`}
        loading={deleteMutation.isPending}
        onConfirm={() => deleting && deleteMutation.mutate(deleting.id)}
        onCancel={() => setDeleting(null)}
      />
    </div>
  );
}

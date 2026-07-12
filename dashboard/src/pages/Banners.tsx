import { useRef, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Pencil, Plus, Trash2, Upload } from 'lucide-react';
import {
  adminCategoriesService,
  adminProductsService,
  bannersService,
  flattenTree,
  getErrorMessage,
  uploadMedia,
} from '@/api';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/Button';
import { Badge } from '@/components/Badge';
import { Modal } from '@/components/Modal';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { DataTable, type Column } from '@/components/DataTable';
import { useLocaleStore } from '@/store/locale';
import { toast } from '@/store/toast';
import type { AdminBanner, AdminBannerInput, BannerLinkType } from '@/types';

interface FormState {
  image_url: string;
  title: string;
  subtitle: string;
  cta_text: string;
  link_type: BannerLinkType;
  link_value: string;
  sort_order: number;
  is_active: boolean;
  starts_at: string;
  ends_at: string;
}

function toForm(b?: AdminBanner): FormState {
  return {
    image_url: b?.image_url ?? '',
    title: b?.title ?? '',
    subtitle: b?.subtitle ?? '',
    cta_text: b?.cta_text ?? '',
    link_type: b?.link_type ?? 'none',
    link_value: b?.link_value ?? '',
    sort_order: b?.sort_order ?? 0,
    is_active: b?.is_active ?? true,
    starts_at: b?.starts_at?.slice(0, 10) ?? '',
    ends_at: b?.ends_at?.slice(0, 10) ?? '',
  };
}

function BannerForm({
  initial,
  submitting,
  onSubmit,
  onCancel,
}: {
  initial?: AdminBanner;
  submitting?: boolean;
  onSubmit: (v: AdminBannerInput) => void;
  onCancel: () => void;
}) {
  const [form, setForm] = useState<FormState>(toForm(initial));
  const [uploading, setUploading] = useState(false);
  const fileRef = useRef<HTMLInputElement>(null);

  // Deep-link target pickers — only fetched when needed.
  const categoriesQuery = useQuery({
    queryKey: ['admin-categories'],
    queryFn: () => adminCategoriesService.tree(),
    enabled: form.link_type === 'category',
  });
  const productsQuery = useQuery({
    queryKey: ['admin-products-picker'],
    queryFn: () => adminProductsService.list({ per_page: 200 }),
    enabled: form.link_type === 'product',
  });

  const set = <K extends keyof FormState>(key: K, value: FormState[K]) =>
    setForm((prev) => ({ ...prev, [key]: value }));

  const onPickImage = async (file: File | undefined) => {
    if (!file) return;
    setUploading(true);
    try {
      const url = await uploadMedia(file);
      set('image_url', url);
      toast.success('Image uploaded');
    } catch (e) {
      toast.error(getErrorMessage(e));
    } finally {
      setUploading(false);
    }
  };

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.image_url) {
      toast.error('An image is required');
      return;
    }
    if (form.link_type !== 'none' && !form.link_value.trim()) {
      toast.error('This link target needs a value');
      return;
    }
    onSubmit({
      image_url: form.image_url,
      title: form.title || null,
      subtitle: form.subtitle || null,
      cta_text: form.cta_text || null,
      link_type: form.link_type,
      link_value: form.link_type === 'none' ? null : form.link_value.trim(),
      sort_order: Number(form.sort_order),
      is_active: form.is_active,
      starts_at: form.starts_at || null,
      ends_at: form.ends_at || null,
    });
  };

  return (
    <form onSubmit={submit} className="space-y-4">
      {/* Image */}
      <div>
        <label className="label">Image (≈16:10)</label>
        <div className="flex items-center gap-3">
          {form.image_url ? (
            <img
              src={form.image_url}
              alt="banner"
              className="h-20 w-32 rounded-lg border border-slate-200 object-cover dark:border-slate-700"
            />
          ) : (
            <div className="flex h-20 w-32 items-center justify-center rounded-lg border border-dashed border-slate-300 text-xs text-slate-400 dark:border-slate-700">
              None
            </div>
          )}
          <input
            ref={fileRef}
            type="file"
            accept="image/*"
            className="hidden"
            onChange={(e) => onPickImage(e.target.files?.[0])}
          />
          <Button
            type="button"
            variant="outline"
            size="sm"
            loading={uploading}
            icon={<Upload size={16} />}
            onClick={() => fileRef.current?.click()}
          >
            Upload
          </Button>
        </div>
        <input
          className="input mt-2 font-mono text-xs"
          placeholder="…or paste an image URL"
          value={form.image_url}
          onChange={(e) => set('image_url', e.target.value)}
        />
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className="label">Title</label>
          <input
            className="input"
            value={form.title}
            onChange={(e) => set('title', e.target.value)}
          />
        </div>
        <div>
          <label className="label">CTA text</label>
          <input
            className="input"
            value={form.cta_text}
            onChange={(e) => set('cta_text', e.target.value)}
          />
        </div>
      </div>

      <div>
        <label className="label">Subtitle</label>
        <input
          className="input"
          value={form.subtitle}
          onChange={(e) => set('subtitle', e.target.value)}
        />
      </div>

      {/* Deep-link target */}
      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className="label">Link target</label>
          <select
            className="input"
            value={form.link_type}
            onChange={(e) => {
              set('link_type', e.target.value as BannerLinkType);
              set('link_value', '');
            }}
          >
            <option value="none">None</option>
            <option value="category">Category</option>
            <option value="product">Product</option>
            <option value="url">URL</option>
          </select>
        </div>
        <div>
          {form.link_type === 'category' && (
            <>
              <label className="label">Category</label>
              <select
                className="input"
                value={form.link_value}
                onChange={(e) => set('link_value', e.target.value)}
              >
                <option value="">Select…</option>
                {flattenTree(categoriesQuery.data ?? []).map(({ node, depth }) => (
                  <option key={node.id} value={node.slug}>
                    {' '.repeat(depth * 2)}
                    {node.name.en || node.slug}
                  </option>
                ))}
              </select>
            </>
          )}
          {form.link_type === 'product' && (
            <>
              <label className="label">Product</label>
              <select
                className="input"
                value={form.link_value}
                onChange={(e) => set('link_value', e.target.value)}
              >
                <option value="">Select…</option>
                {(productsQuery.data ?? []).map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.name.en}
                  </option>
                ))}
              </select>
            </>
          )}
          {form.link_type === 'url' && (
            <>
              <label className="label">URL</label>
              <input
                className="input"
                placeholder="https://…"
                value={form.link_value}
                onChange={(e) => set('link_value', e.target.value)}
              />
            </>
          )}
        </div>
      </div>

      <div className="grid grid-cols-3 gap-4">
        <div>
          <label className="label">Sort order</label>
          <input
            type="number"
            className="input"
            value={form.sort_order}
            onChange={(e) => set('sort_order', Number(e.target.value))}
          />
        </div>
        <div>
          <label className="label">Starts at</label>
          <input
            type="date"
            className="input"
            value={form.starts_at}
            onChange={(e) => set('starts_at', e.target.value)}
          />
        </div>
        <div>
          <label className="label">Ends at</label>
          <input
            type="date"
            className="input"
            value={form.ends_at}
            onChange={(e) => set('ends_at', e.target.value)}
          />
        </div>
      </div>

      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          className="h-4 w-4 rounded"
          checked={form.is_active}
          onChange={(e) => set('is_active', e.target.checked)}
        />
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

function linkLabel(b: AdminBanner): string {
  if (b.link_type === 'none') return '—';
  return `${b.link_type}: ${b.link_value ?? ''}`;
}

export function Banners() {
  const t = useLocaleStore((s) => s.t);
  const qc = useQueryClient();
  const [creating, setCreating] = useState(false);
  const [editing, setEditing] = useState<AdminBanner | null>(null);
  const [deleting, setDeleting] = useState<AdminBanner | null>(null);

  const query = useQuery({
    queryKey: ['banners'],
    queryFn: () => bannersService.list(),
  });

  const invalidate = () => qc.invalidateQueries({ queryKey: ['banners'] });

  const createMutation = useMutation({
    mutationFn: (input: AdminBannerInput) => bannersService.create(input),
    onSuccess: () => {
      invalidate();
      setCreating(false);
      toast.success('Banner created');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: string; input: AdminBannerInput }) =>
      bannersService.update(id, input),
    onSuccess: () => {
      invalidate();
      setEditing(null);
      toast.success('Banner updated');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const toggleMutation = useMutation({
    mutationFn: ({ id, is_active }: { id: string; is_active: boolean }) =>
      bannersService.toggleActive(id, is_active),
    onSuccess: () => invalidate(),
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: string) => bannersService.remove(id),
    onSuccess: () => {
      invalidate();
      setDeleting(null);
      toast.success('Banner deleted');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const columns: Column<AdminBanner>[] = [
    {
      key: 'image',
      header: '',
      render: (b) => (
        <img
          src={b.image_url}
          alt=""
          className="h-10 w-16 rounded object-cover"
        />
      ),
    },
    {
      key: 'title',
      header: 'Title',
      render: (b) => (
        <div>
          <p className="font-medium">{b.title || '—'}</p>
          {b.subtitle && (
            <p className="text-xs text-slate-400">{b.subtitle}</p>
          )}
        </div>
      ),
    },
    { key: 'link', header: 'Link', render: (b) => <span className="font-mono text-xs">{linkLabel(b)}</span> },
    { key: 'sort', header: 'Sort', render: (b) => b.sort_order },
    {
      key: 'active',
      header: 'Active',
      render: (b) => (
        <button
          onClick={() =>
            toggleMutation.mutate({ id: b.id, is_active: !b.is_active })
          }
          className="cursor-pointer"
        >
          <Badge tone={b.is_active ? 'green' : 'gray'}>
            {b.is_active ? t('active') : t('inactive')}
          </Badge>
        </button>
      ),
    },
    {
      key: 'actions',
      header: '',
      className: 'text-end',
      render: (b) => (
        <div className="flex justify-end gap-1">
          <Button variant="ghost" size="sm" onClick={() => setEditing(b)}>
            <Pencil size={16} />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            className="text-red-500"
            onClick={() => setDeleting(b)}
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
        title={t('nav_banners')}
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
          rowKey={(b) => b.id}
          loading={query.isLoading}
          error={query.error ? getErrorMessage(query.error) : null}
          onRetry={() => query.refetch()}
        />
      </div>

      <Modal open={creating} title="New banner" onClose={() => setCreating(false)} size="lg">
        <BannerForm
          submitting={createMutation.isPending}
          onSubmit={(v) => createMutation.mutate(v)}
          onCancel={() => setCreating(false)}
        />
      </Modal>

      <Modal open={!!editing} title="Edit banner" onClose={() => setEditing(null)} size="lg">
        {editing && (
          <BannerForm
            initial={editing}
            submitting={updateMutation.isPending}
            onSubmit={(v) => updateMutation.mutate({ id: editing.id, input: v })}
            onCancel={() => setEditing(null)}
          />
        )}
      </Modal>

      <ConfirmDialog
        open={!!deleting}
        title="Delete banner"
        message={`Delete this banner?`}
        loading={deleteMutation.isPending}
        onConfirm={() => deleting && deleteMutation.mutate(deleting.id)}
        onCancel={() => setDeleting(null)}
      />
    </div>
  );
}

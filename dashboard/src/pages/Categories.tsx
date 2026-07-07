import { useMemo, useRef, useState } from 'react';
import { useForm } from 'react-hook-form';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  ChevronDown,
  ChevronRight,
  Pencil,
  Plus,
  Trash2,
  Upload,
} from 'lucide-react';
import {
  adminCategoriesService,
  flattenTree,
  getErrorMessage,
  subtreeIds,
  uploadMedia,
} from '@/api';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/Button';
import { Badge } from '@/components/Badge';
import { Modal } from '@/components/Modal';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { LoadingState, ErrorState, EmptyState } from '@/components/States';
import { useLocaleStore } from '@/store/locale';
import { toast } from '@/store/toast';
import type { CategoryNode, CategoryNodeInput, Locale } from '@/types';

function nodeName(node: CategoryNode, locale: Locale): string {
  return node.name[locale] || node.name.en || node.slug;
}

interface FormValues {
  name_en: string;
  name_ar: string;
  slug: string;
  parent_id: string;
  icon_key: string;
  sort_order: number;
}

function CategoryForm({
  initial,
  defaultParentId,
  tree,
  submitting,
  onSubmit,
  onCancel,
}: {
  initial?: CategoryNode;
  defaultParentId?: string | null;
  tree: CategoryNode[];
  submitting?: boolean;
  onSubmit: (v: CategoryNodeInput) => void;
  onCancel: () => void;
}) {
  const fileRef = useRef<HTMLInputElement>(null);
  const [imageUrl, setImageUrl] = useState<string | null>(
    initial?.image_url ?? null,
  );
  const [uploading, setUploading] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    defaultValues: {
      name_en: initial?.name.en ?? '',
      name_ar: initial?.name.ar ?? '',
      slug: initial?.slug ?? '',
      parent_id: initial?.parent_id ?? defaultParentId ?? '',
      icon_key: initial?.icon_key ?? '',
      sort_order: initial?.sort_order ?? 0,
    },
  });

  // Parent options exclude self + its subtree (no cycles).
  const excluded = useMemo(
    () => (initial ? subtreeIds(initial) : []),
    [initial],
  );
  const parentOptions = useMemo(
    () => flattenTree(tree).filter(({ node }) => !excluded.includes(node.id)),
    [tree, excluded],
  );

  const onPickImage = async (file: File | undefined) => {
    if (!file) return;
    setUploading(true);
    try {
      setImageUrl(await uploadMedia(file));
      toast.success('Image uploaded');
    } catch (e) {
      toast.error(getErrorMessage(e));
    } finally {
      setUploading(false);
    }
  };

  return (
    <form
      onSubmit={handleSubmit((v) =>
        onSubmit({
          name: { en: v.name_en.trim(), ar: v.name_ar.trim() },
          slug: v.slug.trim().toLowerCase() || undefined,
          parent_id: v.parent_id || null,
          icon_key: v.icon_key.trim() || null,
          image_url: imageUrl,
          sort_order: Number(v.sort_order),
        }),
      )}
      className="space-y-4"
    >
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label className="label">Name (EN)</label>
          <input className="input" {...register('name_en', { required: 'Required' })} />
          {errors.name_en && <p className="field-error">{errors.name_en.message}</p>}
        </div>
        <div>
          <label className="label">Name (AR)</label>
          <input
            className="input"
            dir="rtl"
            {...register('name_ar', { required: 'Required' })}
          />
          {errors.name_ar && <p className="field-error">{errors.name_ar.message}</p>}
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label className="label">Slug {initial && '(read-only)'}</label>
          <input
            className="input"
            placeholder="auto from name"
            disabled={!!initial}
            {...register('slug')}
          />
        </div>
        <div>
          <label className="label">Parent</label>
          <select className="input" {...register('parent_id')}>
            <option value="">— Top-level (department) —</option>
            {parentOptions.map(({ node, depth }) => (
              <option key={node.id} value={node.id}>
                {`${'  '.repeat(depth)}${node.name.en}`}
              </option>
            ))}
          </select>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label className="label">Icon key</label>
          <input className="input" placeholder="tshirt" {...register('icon_key')} />
        </div>
        <div>
          <label className="label">Sort order</label>
          <input type="number" className="input" {...register('sort_order')} />
        </div>
      </div>

      <div>
        <label className="label">Image</label>
        <div className="flex items-center gap-3">
          {imageUrl ? (
            <img
              src={imageUrl}
              alt=""
              className="h-14 w-14 rounded-lg border border-slate-200 object-cover dark:border-slate-700"
            />
          ) : (
            <div className="flex h-14 w-14 items-center justify-center rounded-lg border border-dashed border-slate-300 text-xs text-slate-400 dark:border-slate-700">
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
          {imageUrl && (
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={() => setImageUrl(null)}
            >
              Remove
            </Button>
          )}
        </div>
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

function TreeRow({
  node,
  depth,
  locale,
  onEdit,
  onDelete,
  onAddChild,
}: {
  node: CategoryNode;
  depth: number;
  locale: Locale;
  onEdit: (n: CategoryNode) => void;
  onDelete: (n: CategoryNode) => void;
  onAddChild: (n: CategoryNode) => void;
}) {
  const [open, setOpen] = useState(true);
  const hasChildren = node.children.length > 0;

  return (
    <>
      <div
        className="flex items-center gap-2 rounded-lg px-2 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/40"
        style={{ paddingInlineStart: `${depth * 20 + 8}px` }}
      >
        <button
          type="button"
          onClick={() => hasChildren && setOpen((o) => !o)}
          className={`text-slate-400 ${hasChildren ? '' : 'invisible'}`}
          aria-label="Toggle"
        >
          {open ? <ChevronDown size={16} /> : <ChevronRight size={16} />}
        </button>

        <div className="flex-1">
          <div className="flex items-center gap-2">
            <span className="font-medium">{nodeName(node, locale)}</span>
            <Badge tone={node.is_leaf ? 'blue' : 'purple'}>
              {node.is_leaf ? 'leaf' : 'department'}
            </Badge>
            <span className="font-mono text-xs text-slate-400">{node.slug}</span>
          </div>
        </div>

        <span className="text-xs text-slate-400">
          {node.product_count} product(s)
        </span>

        <div className="flex items-center gap-1">
          <Button
            variant="ghost"
            size="sm"
            title="Add subcategory"
            onClick={() => onAddChild(node)}
          >
            <Plus size={16} />
          </Button>
          <Button variant="ghost" size="sm" onClick={() => onEdit(node)}>
            <Pencil size={16} />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            className="text-red-500"
            onClick={() => onDelete(node)}
          >
            <Trash2 size={16} />
          </Button>
        </div>
      </div>

      {open &&
        node.children.map((child) => (
          <TreeRow
            key={child.id}
            node={child}
            depth={depth + 1}
            locale={locale}
            onEdit={onEdit}
            onDelete={onDelete}
            onAddChild={onAddChild}
          />
        ))}
    </>
  );
}

export function Categories() {
  const { t, locale } = useLocaleStore();
  const qc = useQueryClient();
  const [creating, setCreating] = useState(false);
  const [createParent, setCreateParent] = useState<CategoryNode | null>(null);
  const [editing, setEditing] = useState<CategoryNode | null>(null);
  const [deleting, setDeleting] = useState<CategoryNode | null>(null);

  const query = useQuery({
    queryKey: ['admin-categories'],
    queryFn: () => adminCategoriesService.tree(),
  });
  const tree = query.data ?? [];

  const invalidate = () =>
    qc.invalidateQueries({ queryKey: ['admin-categories'] });

  const createMutation = useMutation({
    mutationFn: (input: CategoryNodeInput) =>
      adminCategoriesService.create(input),
    onSuccess: () => {
      invalidate();
      setCreating(false);
      setCreateParent(null);
      toast.success('Category created');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: string; input: CategoryNodeInput }) =>
      adminCategoriesService.update(id, input),
    onSuccess: () => {
      invalidate();
      setEditing(null);
      toast.success('Category updated');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: string) => adminCategoriesService.remove(id),
    onSuccess: () => {
      invalidate();
      setDeleting(null);
      toast.success('Category deleted');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const openCreate = (parent: CategoryNode | null) => {
    setCreateParent(parent);
    setCreating(true);
  };

  return (
    <div>
      <PageHeader
        title={t('nav_categories')}
        subtitle="Departments → subcategories → leaves"
        actions={
          <Button icon={<Plus size={16} />} onClick={() => openCreate(null)}>
            {t('create')}
          </Button>
        }
      />

      <div className="card p-3">
        {query.isLoading ? (
          <LoadingState />
        ) : query.error ? (
          <ErrorState
            message={getErrorMessage(query.error)}
            onRetry={() => query.refetch()}
          />
        ) : tree.length === 0 ? (
          <EmptyState />
        ) : (
          <div className="divide-y divide-slate-100 dark:divide-slate-800/60">
            {tree.map((node) => (
              <TreeRow
                key={node.id}
                node={node}
                depth={0}
                locale={locale}
                onEdit={setEditing}
                onDelete={setDeleting}
                onAddChild={openCreate}
              />
            ))}
          </div>
        )}
      </div>

      <Modal
        open={creating}
        title={
          createParent
            ? `New subcategory in "${createParent.name.en}"`
            : 'New department'
        }
        onClose={() => {
          setCreating(false);
          setCreateParent(null);
        }}
        size="lg"
      >
        <CategoryForm
          key={createParent?.id ?? 'root'}
          tree={tree}
          defaultParentId={createParent?.id ?? null}
          submitting={createMutation.isPending}
          onSubmit={(v) => createMutation.mutate(v)}
          onCancel={() => {
            setCreating(false);
            setCreateParent(null);
          }}
        />
      </Modal>

      <Modal
        open={!!editing}
        title="Edit category"
        onClose={() => setEditing(null)}
        size="lg"
      >
        {editing && (
          <CategoryForm
            initial={editing}
            tree={tree}
            submitting={updateMutation.isPending}
            onSubmit={(v) =>
              updateMutation.mutate({ id: editing.id, input: v })
            }
            onCancel={() => setEditing(null)}
          />
        )}
      </Modal>

      <ConfirmDialog
        open={!!deleting}
        title="Delete category"
        message={
          deleting
            ? `Delete "${deleting.name.en}"? Categories with children or products cannot be deleted.`
            : ''
        }
        loading={deleteMutation.isPending}
        onConfirm={() => deleting && deleteMutation.mutate(deleting.id)}
        onCancel={() => setDeleting(null)}
      />
    </div>
  );
}

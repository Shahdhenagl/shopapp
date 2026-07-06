import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Pencil, Plus, Search, Star, Trash2 } from 'lucide-react';
import {
  categoriesService,
  getErrorMessage,
  productsService,
} from '@/api';
import type { ProductInput } from '@/api/products';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/Button';
import { Badge } from '@/components/Badge';
import { Modal } from '@/components/Modal';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { DataTable, type Column } from '@/components/DataTable';
import { ProductForm } from './ProductForm';
import { useLocaleStore } from '@/store/locale';
import { toast } from '@/store/toast';
import { formatMoney } from '@/lib/format';
import type { Product } from '@/types';

export function Products() {
  const t = useLocaleStore((s) => s.t);
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('');
  const [editing, setEditing] = useState<Product | null>(null);
  const [creating, setCreating] = useState(false);
  const [deleting, setDeleting] = useState<Product | null>(null);

  const categoriesQuery = useQuery({
    queryKey: ['categories'],
    queryFn: () => categoriesService.list(),
  });

  const productsQuery = useQuery({
    queryKey: ['products'],
    queryFn: () => productsService.list(),
  });

  const filtered = useMemo(() => {
    const items = productsQuery.data ?? [];
    return items.filter((p) => {
      const matchesCat = !category || p.category_id === category;
      const q = search.toLowerCase();
      const matchesSearch =
        !q ||
        p.name.toLowerCase().includes(q) ||
        p.style.toLowerCase().includes(q);
      return matchesCat && matchesSearch;
    });
  }, [productsQuery.data, category, search]);

  const createMutation = useMutation({
    mutationFn: (input: ProductInput) => productsService.create(input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products'] });
      setCreating(false);
      toast.success('Product created');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: string; input: ProductInput }) =>
      productsService.update(id, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products'] });
      setEditing(null);
      toast.success('Product updated');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: string) => productsService.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products'] });
      setDeleting(null);
      toast.success('Product deleted');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const categories = categoriesQuery.data ?? [];

  const columns: Column<Product>[] = [
    {
      key: 'image',
      header: '',
      render: (p) => (
        <img
          src={p.images[0]}
          alt={p.name}
          className="h-12 w-12 rounded-lg object-cover"
          loading="lazy"
        />
      ),
    },
    {
      key: 'name',
      header: t('nav_products'),
      render: (p) => (
        <div>
          <p className="font-medium">{p.name}</p>
          <p className="text-xs text-slate-400">{p.style}</p>
        </div>
      ),
    },
    {
      key: 'category',
      header: t('nav_categories'),
      render: (p) => <Badge tone="blue">{p.category_id}</Badge>,
    },
    {
      key: 'price',
      header: 'Price',
      render: (p) => formatMoney(p.price, p.currency),
    },
    {
      key: 'rating',
      header: 'Rating',
      render: (p) => (
        <span className="inline-flex items-center gap-1">
          <Star size={14} className="fill-yellow-400 text-yellow-400" />
          {p.rating}
        </span>
      ),
    },
    {
      key: 'newest',
      header: '',
      render: (p) => (p.is_newest ? <Badge tone="green">Newest</Badge> : null),
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
        title={t('nav_products')}
        subtitle={`${filtered.length} item(s)`}
        actions={
          <Button icon={<Plus size={16} />} onClick={() => setCreating(true)}>
            {t('create')}
          </Button>
        }
      />

      <div className="card mb-4 flex flex-wrap items-center gap-3 p-4">
        <div className="relative flex-1 min-w-[200px]">
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
          className="input max-w-[200px]"
          value={category}
          onChange={(e) => setCategory(e.target.value)}
        >
          <option value="">{t('all_categories')}</option>
          {categories.map((c) => (
            <option key={c.id} value={c.id}>
              {c.id}
            </option>
          ))}
        </select>
      </div>

      <div className="card p-2">
        <DataTable
          columns={columns}
          rows={filtered}
          rowKey={(p) => p.id}
          loading={productsQuery.isLoading}
          error={productsQuery.error ? getErrorMessage(productsQuery.error) : null}
          onRetry={() => productsQuery.refetch()}
        />
      </div>

      <Modal
        open={creating}
        title="New product"
        onClose={() => setCreating(false)}
        size="xl"
      >
        <ProductForm
          categories={categories}
          submitting={createMutation.isPending}
          onSubmit={(input) => createMutation.mutate(input)}
          onCancel={() => setCreating(false)}
        />
      </Modal>

      <Modal
        open={!!editing}
        title="Edit product"
        onClose={() => setEditing(null)}
        size="xl"
      >
        {editing && (
          <ProductForm
            initial={editing}
            categories={categories}
            submitting={updateMutation.isPending}
            onSubmit={(input) =>
              updateMutation.mutate({ id: editing.id, input })
            }
            onCancel={() => setEditing(null)}
          />
        )}
      </Modal>

      <ConfirmDialog
        open={!!deleting}
        title="Delete product"
        message={`Delete "${deleting?.name}"? This cannot be undone.`}
        loading={deleteMutation.isPending}
        onConfirm={() => deleting && deleteMutation.mutate(deleting.id)}
        onCancel={() => setDeleting(null)}
      />
    </div>
  );
}

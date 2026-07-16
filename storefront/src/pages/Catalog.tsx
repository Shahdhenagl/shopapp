import { useMemo } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { catalog, getErrorMessage } from '@/api';
import { ProductCard } from '@/components/ProductCard';
import { Empty, ErrorState, Skeleton } from '@/components/States';
import type { Category } from '@/types';

function flatten(nodes: Category[]): Category[] {
  return nodes.flatMap((n) => [n, ...flatten(n.children ?? [])]);
}

export function Catalog() {
  const { categoryId } = useParams();
  const [params] = useSearchParams();
  const search = params.get('q') ?? '';

  const categoriesQuery = useQuery({
    queryKey: ['categories'],
    queryFn: () => catalog.categories(),
  });

  const productsQuery = useQuery({
    queryKey: ['products', { categoryId, search }],
    queryFn: () =>
      catalog.products({
        category: categoryId || undefined,
        search: search || undefined,
        per_page: 60,
      }),
  });

  const categories = useMemo(
    () => flatten(categoriesQuery.data ?? []),
    [categoriesQuery.data],
  );
  const current = categories.find((c) => c.id === categoryId);
  const products = productsQuery.data ?? [];

  const title = search
    ? `نتائج البحث عن "${search}"`
    : (current?.name ?? 'كل المنتجات');

  return (
    <div>
      <h1 className="text-title font-bold text-ink">{title}</h1>

      {/* Sibling / child categories */}
      {categories.length > 0 && (
        <div className="mt-3 flex gap-2 overflow-x-auto pb-1">
          <Link
            to="/shop"
            className={`pill flex-none ${!categoryId ? 'pill--active' : ''}`}
          >
            الكل
          </Link>
          {categories
            .filter((c) => !c.parent_id)
            .map((c) => (
              <Link
                key={c.id}
                to={`/c/${c.id}`}
                className={`pill flex-none ${c.id === categoryId ? 'pill--active' : ''}`}
              >
                {c.name}
              </Link>
            ))}
        </div>
      )}

      {productsQuery.isLoading ? (
        <div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
          {Array.from({ length: 8 }).map((_, i) => (
            <Skeleton key={i} className="aspect-[3/4]" />
          ))}
        </div>
      ) : productsQuery.error ? (
        <ErrorState
          message={getErrorMessage(productsQuery.error)}
          onRetry={() => productsQuery.refetch()}
        />
      ) : products.length === 0 ? (
        <Empty label="لا توجد منتجات مطابقة." />
      ) : (
        <div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
          {products.map((p) => (
            <ProductCard key={p.id} product={p} />
          ))}
        </div>
      )}
    </div>
  );
}

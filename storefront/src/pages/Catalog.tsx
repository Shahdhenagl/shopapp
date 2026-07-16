import { useMemo } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { catalog, getErrorMessage } from '@/api';
import { ProductCard } from '@/components/ProductCard';
import { Empty, ErrorState, Skeleton } from '@/components/States';
import { childrenOf, departments as topLevel } from '@/lib/categories';

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

  const categories = categoriesQuery.data ?? [];
  const current = categories.find((c) => c.id === categoryId);
  const products = productsQuery.data ?? [];

  // Browsing a department? Offer its sub-categories. Otherwise the departments.
  const siblings = useMemo(() => {
    if (!current) return topLevel(categories);
    const children = childrenOf(categories, current.id);
    return children.length > 0
      ? children
      : childrenOf(categories, current.parent_id ?? '');
  }, [categories, current]);

  const title = search
    ? `نتائج البحث عن "${search}"`
    : (current?.name ?? 'كل المنتجات');

  return (
    <div>
      <h1 className="text-title font-bold text-ink">{title}</h1>

      {/* Departments, or the current department's sub-categories. */}
      {siblings.length > 0 && (
        <div className="-mx-4 mt-3 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          <Link
            to="/shop"
            className={`pill flex-none ${!categoryId ? 'pill--active' : ''}`}
          >
            الكل
          </Link>
          {siblings.map((c) => (
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

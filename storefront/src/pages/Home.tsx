import { useMemo } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { catalog, getErrorMessage } from '@/api';
import { ProductCard } from '@/components/ProductCard';
import { ErrorState, Skeleton } from '@/components/States';
import type { Category, Product } from '@/types';

/** Flatten the category tree so a rail can be scoped to a whole department. */
function flatten(nodes: Category[]): Category[] {
  return nodes.flatMap((n) => [n, ...flatten(n.children ?? [])]);
}

function subtreeIds(node: Category): string[] {
  return [node.id, ...(node.children ?? []).flatMap(subtreeIds)];
}

function Rail({
  title,
  to,
  products,
}: {
  title: string;
  to: string;
  products: Product[];
}) {
  // A promoted category with nothing in it never renders a bare header.
  if (products.length === 0) return null;

  return (
    <section className="mt-6">
      <div className="mb-3 flex items-center justify-between">
        <h2 className="text-title font-bold text-ink">{title}</h2>
        <Link to={to} className="text-body font-semibold text-accent">
          عرض الكل
        </Link>
      </div>
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        {products.map((p) => (
          <ProductCard key={p.id} product={p} />
        ))}
      </div>
    </section>
  );
}

export function Home() {
  const settingsQuery = useQuery({
    queryKey: ['settings'],
    queryFn: () => catalog.settings(),
  });
  const categoriesQuery = useQuery({
    queryKey: ['categories'],
    queryFn: () => catalog.categories(),
  });
  const bannersQuery = useQuery({
    queryKey: ['banners'],
    queryFn: () => catalog.banners(),
  });
  const productsQuery = useQuery({
    queryKey: ['products', 'home'],
    queryFn: () => catalog.products({ per_page: 60 }),
  });

  const settings = settingsQuery.data;
  const categories = useMemo(
    () => flatten(categoriesQuery.data ?? []),
    [categoriesQuery.data],
  );
  const products = productsQuery.data ?? [];

  // Dashboard-curated rails: ordered ids, unknown/duplicate ids ignored, empty
  // rails dropped, capped — the client owns all of this (BACKEND.md §7).
  const rails = useMemo(() => {
    const maxRails = Math.max(0, Math.min(20, settings?.max_home_rails ?? 8));
    const perRail = Math.max(1, Math.min(20, settings?.home_rail_item_count ?? 5));
    const ids = settings?.home_rail_categories ?? [];

    const seen = new Set<string>();
    const out: { category: Category; products: Product[] }[] = [];

    for (const id of ids) {
      if (out.length >= maxRails) break;
      if (!id || seen.has(id)) continue;
      seen.add(id);

      const category = categories.find((c) => c.id === id);
      if (!category) continue; // unknown id — doesn't consume a slot

      const scope = new Set(subtreeIds(category));
      const railProducts = products
        .filter((p) => scope.has(p.category_id))
        .slice(0, perRail);

      if (railProducts.length > 0) out.push({ category, products: railProducts });
    }

    return out;
  }, [settings, categories, products]);

  const newest = products.filter((p) => p.is_newest).slice(0, 10);
  const banners = bannersQuery.data ?? [];

  const bannerHref = (link_type: string, link_value: string | null) => {
    if (!link_value) return '/shop';
    if (link_type === 'category') return `/c/${link_value}`;
    if (link_type === 'product') return `/p/${link_value}`;
    if (link_type === 'url') return link_value;
    return '/shop';
  };

  if (settingsQuery.error) {
    return (
      <ErrorState
        message={getErrorMessage(settingsQuery.error)}
        onRetry={() => settingsQuery.refetch()}
      />
    );
  }

  return (
    <div>
      {/* Hero banners */}
      {bannersQuery.isLoading ? (
        <Skeleton className="aspect-[16/7] w-full" />
      ) : banners.length > 0 ? (
        <div className="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-1">
          {banners.map((b) => (
            <Link
              key={b.id}
              to={bannerHref(b.link_type, b.link_value)}
              className="relative aspect-[16/7] w-full flex-none snap-start overflow-hidden rounded-card bg-surface-variant sm:w-[70%]"
            >
              <img
                src={b.image_url}
                alt={b.title ?? ''}
                className="h-full w-full object-cover"
              />
              {(b.title || b.cta_text) && (
                <div className="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-black/60 to-transparent p-4 text-white">
                  {b.title && <p className="text-title font-bold">{b.title}</p>}
                  {b.subtitle && <p className="text-body">{b.subtitle}</p>}
                  {b.cta_text && (
                    <span className="mt-2 w-fit rounded-pill bg-white/90 px-3 py-1 text-caption font-bold text-ink">
                      {b.cta_text}
                    </span>
                  )}
                </div>
              )}
            </Link>
          ))}
        </div>
      ) : null}

      {/* Category pills on their sky panel */}
      {categories.length > 0 && (
        <section className="mt-5 rounded-card bg-section-fill p-3">
          <div className="flex gap-2 overflow-x-auto">
            {categories
              .filter((c) => !c.parent_id)
              .map((c) => (
                <Link key={c.id} to={`/c/${c.id}`} className="pill flex-none">
                  {c.name}
                </Link>
              ))}
          </div>
        </section>
      )}

      {/* Newest */}
      {productsQuery.isLoading ? (
        <div className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className="aspect-[3/4]" />
          ))}
        </div>
      ) : (
        <Rail title="وصل حديثًا" to="/shop" products={newest} />
      )}

      {/* Dashboard-curated rails */}
      {rails.map(({ category, products: railProducts }) => (
        <Rail
          key={category.id}
          title={category.name}
          to={`/c/${category.id}`}
          products={railProducts}
        />
      ))}
    </div>
  );
}

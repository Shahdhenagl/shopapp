import { useMemo } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ChevronLeft, ShoppingBag } from 'lucide-react';
import { catalog, getErrorMessage } from '@/api';
import { ProductCard } from '@/components/ProductCard';
import { ErrorState, Skeleton } from '@/components/States';
import { departments as topLevel, subtreeIds } from '@/lib/categories';
import type { Category, Product } from '@/types';

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
    <section className="mt-8">
      <div className="mb-3 flex items-baseline justify-between">
        <h2 className="text-title font-bold text-ink">{title}</h2>
        <Link
          to={to}
          className="flex items-center gap-0.5 text-body font-semibold text-accent"
        >
          عرض الكل <ChevronLeft size={15} />
        </Link>
      </div>
      <div className="grid grid-cols-2 gap-x-3 gap-y-5 sm:grid-cols-3 lg:grid-cols-5">
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
  const categories = categoriesQuery.data ?? [];
  const products = productsQuery.data ?? [];
  const departments = useMemo(() => topLevel(categories), [categories]);

  // Dashboard-curated rails: ordered ids, unknown/duplicate ids ignored, empty
  // rails dropped, capped — the client owns all of this.
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

      // A department promotes its whole subtree, not just its own products.
      const scope = new Set(subtreeIds(categories, category.id));
      const railProducts = products
        .filter((p) => scope.has(p.category_id))
        .slice(0, perRail);

      if (railProducts.length > 0) out.push({ category, products: railProducts });
    }

    return out;
  }, [settings, categories, products]);

  const newest = products.filter((p) => p.is_newest).slice(0, 10);
  const banners = bannersQuery.data ?? [];

  const bannerHref = (linkType: string, linkValue: string | null) => {
    if (!linkValue) return '/shop';
    if (linkType === 'category') return `/c/${linkValue}`;
    if (linkType === 'product') return `/p/${linkValue}`;
    if (linkType === 'url') return linkValue;
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
      {/* Hero — one banner per view, swipeable. */}
      {bannersQuery.isLoading ? (
        <Skeleton className="aspect-[2/1] w-full sm:aspect-[16/6]" />
      ) : banners.length > 0 ? (
        <div className="-mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          {banners.map((b) => (
            <Link
              key={b.id}
              to={bannerHref(b.link_type, b.link_value)}
              className="relative aspect-[2/1] w-full flex-none snap-center overflow-hidden rounded-card bg-surface-variant sm:aspect-[16/6]"
            >
              <img
                src={b.image_url}
                alt={b.title ?? ''}
                className="h-full w-full object-cover"
              />
              {(b.title || b.subtitle || b.cta_text) && (
                <div className="absolute inset-0 flex flex-col items-start justify-end bg-gradient-to-t from-black/75 via-black/25 to-transparent p-5">
                  {b.title && (
                    <p className="text-title font-bold text-white">{b.title}</p>
                  )}
                  {b.subtitle && (
                    <p className="mt-0.5 text-body text-white/85">{b.subtitle}</p>
                  )}
                  {b.cta_text && (
                    <span className="mt-3 rounded-pill bg-white px-4 py-2 text-caption font-bold text-[#0e0e0e]">
                      {b.cta_text}
                    </span>
                  )}
                </div>
              )}
            </Link>
          ))}
        </div>
      ) : null}

      {/* Departments — image tiles rather than a flat bar of words. */}
      {departments.length > 0 && (
        <section className="mt-7">
          <div className="mb-3 flex items-baseline justify-between">
            <h2 className="text-title font-bold text-ink">تسوّق حسب القسم</h2>
            <Link
              to="/shop"
              className="flex items-center gap-0.5 text-body font-semibold text-accent"
            >
              عرض الكل <ChevronLeft size={15} />
            </Link>
          </div>

          <div className="-mx-4 flex gap-4 overflow-x-auto px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            {departments.map((c) => (
              <Link
                key={c.id}
                to={`/c/${c.id}`}
                className="group flex w-16 flex-none flex-col items-center gap-2"
              >
                <span className="grid h-16 w-16 place-items-center overflow-hidden rounded-pill bg-section-fill ring-1 ring-hairline transition group-hover:ring-primary">
                  {c.image_url ? (
                    <img
                      src={c.image_url}
                      alt=""
                      loading="lazy"
                      className="h-full w-full object-cover"
                    />
                  ) : (
                    <ShoppingBag size={20} className="text-accent" />
                  )}
                </span>
                <span className="w-full truncate text-center text-caption font-semibold text-ink">
                  {c.name}
                </span>
              </Link>
            ))}
          </div>
        </section>
      )}

      {/* Newest */}
      {productsQuery.isLoading ? (
        <div className="mt-8 grid grid-cols-2 gap-x-3 gap-y-5 sm:grid-cols-3 lg:grid-cols-5">
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

import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Heart } from 'lucide-react';
import { catalog, getErrorMessage } from '@/api';
import { ProductCard } from '@/components/ProductCard';
import { ErrorState, Loading } from '@/components/States';
import { useFavorites } from '@/hooks/useFavorites';

export function Favorites() {
  const { ids, enabled } = useFavorites();

  // Favourites are ids only — detail comes from the catalog we already load.
  const query = useQuery({
    queryKey: ['products', 'all'],
    queryFn: () => catalog.products({ per_page: 100 }),
    enabled: enabled && ids.length > 0,
  });

  if (!enabled) {
    return (
      <div className="py-16 text-center">
        <Heart className="mx-auto mb-3 text-hint" size={30} />
        <p className="text-body text-muted">سجّل الدخول لحفظ منتجاتك المفضلة.</p>
        <Link to="/login" className="btn btn--sm mt-4">
          تسجيل الدخول
        </Link>
      </div>
    );
  }

  if (ids.length === 0) {
    return (
      <div className="py-16 text-center">
        <Heart className="mx-auto mb-3 text-hint" size={30} />
        <p className="text-body text-muted">لا توجد منتجات في المفضلة بعد.</p>
        <Link to="/shop" className="btn btn--sm mt-4">
          تصفّح المتجر
        </Link>
      </div>
    );
  }

  if (query.isLoading) return <Loading />;
  if (query.error) {
    return (
      <ErrorState
        message={getErrorMessage(query.error)}
        onRetry={() => query.refetch()}
      />
    );
  }

  const products = (query.data ?? []).filter((p) => ids.includes(p.id));

  return (
    <div>
      <h1 className="mb-4 text-title font-bold text-ink">المفضلة</h1>
      <div className="grid grid-cols-2 gap-x-3 gap-y-5 sm:grid-cols-3 lg:grid-cols-4">
        {products.map((p) => (
          <ProductCard key={p.id} product={p} />
        ))}
      </div>
    </div>
  );
}

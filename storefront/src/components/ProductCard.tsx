import { Link, useNavigate } from 'react-router-dom';
import { Heart, ShoppingBag } from 'lucide-react';
import { money, swatch } from '@/lib/format';
import { useFavorites } from '@/hooks/useFavorites';
import type { Product } from '@/types';

export function ProductCard({ product }: { product: Product }) {
  const navigate = useNavigate();
  const { isFavorite, toggle, enabled } = useFavorites();
  const favorite = isFavorite(product.id);

  const onHeart = (e: React.MouseEvent) => {
    // The card is a link — don't navigate when the heart is what was hit.
    e.preventDefault();
    e.stopPropagation();
    if (!enabled) {
      navigate('/login', { state: { from: `/p/${product.id}` } });
      return;
    }
    toggle(product.id);
  };

  return (
    <Link to={`/p/${product.id}`} className="group block">
      <div className="relative aspect-[3/4] overflow-hidden rounded-card bg-surface-variant">
        {product.images[0] ? (
          <img
            src={product.images[0]}
            alt={product.name}
            loading="lazy"
            className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-hint">
            <ShoppingBag size={26} />
          </div>
        )}

        {product.is_newest && (
          <span className="chip chip--sale absolute start-2 top-2">جديد</span>
        )}

        <button
          type="button"
          onClick={onHeart}
          aria-label={favorite ? 'إزالة من المفضلة' : 'إضافة للمفضلة'}
          aria-pressed={favorite}
          className="absolute end-2 top-2 grid h-8 w-8 place-items-center rounded-pill bg-surface/90 backdrop-blur transition hover:scale-105"
        >
          <Heart
            size={15}
            className={favorite ? 'fill-pink text-pink' : 'text-muted'}
          />
        </button>
      </div>

      <div className="pt-2">
        <p className="truncate text-body font-semibold text-ink">
          {product.name}
        </p>
        <div className="mt-1 flex items-center justify-between gap-2">
          <span className="price">{money(product.price, product.currency)}</span>
          {product.colors.length > 0 && (
            <span className="flex items-center gap-1">
              {product.colors.slice(0, 3).map((c) => (
                <span
                  key={c}
                  className="h-3 w-3 rounded-pill border border-hairline"
                  style={{ background: swatch(c) }}
                />
              ))}
              {product.colors.length > 3 && (
                <span className="text-caption text-hint">
                  +{product.colors.length - 3}
                </span>
              )}
            </span>
          )}
        </div>
      </div>
    </Link>
  );
}

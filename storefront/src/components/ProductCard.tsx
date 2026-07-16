import { Link } from 'react-router-dom';
import { ShoppingBag } from 'lucide-react';
import { money } from '@/lib/format';
import type { Product } from '@/types';

export function ProductCard({ product }: { product: Product }) {
  return (
    <Link
      to={`/p/${product.id}`}
      className="group block overflow-hidden rounded-card border border-hairline bg-surface"
    >
      <div className="relative aspect-[3/4] overflow-hidden bg-surface-variant">
        {product.images[0] ? (
          <img
            src={product.images[0]}
            alt={product.name}
            loading="lazy"
            className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-hint">
            <ShoppingBag size={26} />
          </div>
        )}
        {product.is_newest && (
          <span className="chip chip--sale absolute start-2 top-2">جديد</span>
        )}
      </div>

      <div className="p-3">
        <p className="truncate text-body font-semibold text-ink">
          {product.name}
        </p>
        {product.style && (
          <p className="truncate text-caption text-muted">{product.style}</p>
        )}
        <p className="price mt-1 text-body">
          {money(product.price, product.currency)}
        </p>
      </div>
    </Link>
  );
}

import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Check, Minus, Plus, ShoppingBag, Star } from 'lucide-react';
import { cartApi, catalog, getErrorMessage } from '@/api';
import { ErrorState, Loading } from '@/components/States';
import { useAuth } from '@/store/auth';
import { colorToInt, money, swatch } from '@/lib/format';

export function ProductDetail() {
  const { productId } = useParams();
  const navigate = useNavigate();
  const qc = useQueryClient();
  const authed = useAuth((s) => Boolean(s.token));

  const [size, setSize] = useState<string | null>(null);
  const [color, setColor] = useState<string | null>(null);
  const [quantity, setQuantity] = useState(1);
  const [image, setImage] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const [added, setAdded] = useState(false);

  const query = useQuery({
    queryKey: ['product', productId],
    queryFn: () => catalog.product(productId!),
    enabled: Boolean(productId),
  });

  const addMutation = useMutation({
    mutationFn: () =>
      cartApi.add({
        product_id: productId!,
        size: size ?? product!.sizes[0] ?? 'One Size',
        color: color ? colorToInt(color) : 0,
        quantity,
      }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['cart'] });
      setAdded(true);
      setError(null);
      setTimeout(() => setAdded(false), 2000);
    },
    onError: (e) => setError(getErrorMessage(e)),
  });

  if (query.isLoading) return <Loading />;
  if (query.error || !query.data) {
    return (
      <ErrorState
        message={getErrorMessage(query.error)}
        onRetry={() => query.refetch()}
      />
    );
  }

  const product = query.data;

  const addToCart = () => {
    // Signing in is where the cart lives, so send them there and come back.
    if (!authed) {
      navigate('/login', { state: { from: `/p/${productId}` } });
      return;
    }
    if (product.sizes.length > 0 && !size) {
      setError('اختر المقاس أولًا.');
      return;
    }
    if (product.colors.length > 0 && !color) {
      setError('اختر اللون أولًا.');
      return;
    }
    addMutation.mutate();
  };

  return (
    <div className="grid gap-6 lg:grid-cols-2">
      {/* Gallery */}
      <div>
        <div className="aspect-[3/4] overflow-hidden rounded-card bg-surface-variant">
          {product.images[image] ? (
            <img
              src={product.images[image]}
              alt={product.name}
              className="h-full w-full object-cover"
            />
          ) : (
            <div className="flex h-full items-center justify-center text-hint">
              <ShoppingBag size={32} />
            </div>
          )}
        </div>
        {product.images.length > 1 && (
          <div className="mt-3 flex gap-2 overflow-x-auto">
            {product.images.map((src, i) => (
              <button
                key={src}
                onClick={() => setImage(i)}
                className={`h-16 w-16 flex-none overflow-hidden rounded-input border-2 ${
                  i === image ? 'border-primary' : 'border-hairline'
                }`}
              >
                <img src={src} alt="" className="h-full w-full object-cover" />
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Detail */}
      <div>
        <h1 className="text-title font-bold text-ink">{product.name}</h1>
        {product.style && <p className="text-body text-muted">{product.style}</p>}

        <div className="mt-2 flex items-center gap-2">
          <span className="price text-title">
            {money(product.price, product.currency)}
          </span>
          {product.rating > 0 && (
            <span className="flex items-center gap-1 text-caption text-muted">
              <Star size={13} className="fill-warning text-warning" />
              {product.rating}
            </span>
          )}
        </div>

        {product.sizes.length > 0 && (
          <div className="mt-5">
            <p className="label">المقاس</p>
            <div className="flex flex-wrap gap-2">
              {product.sizes.map((s) => (
                <button
                  key={s}
                  onClick={() => {
                    setSize(s);
                    setError(null);
                  }}
                  className={`pill ${s === size ? 'pill--active' : ''}`}
                >
                  {s}
                </button>
              ))}
            </div>
          </div>
        )}

        {product.colors.length > 0 && (
          <div className="mt-5">
            <p className="label">اللون</p>
            <div className="flex flex-wrap gap-2">
              {product.colors.map((c) => (
                <button
                  key={c}
                  onClick={() => {
                    setColor(c);
                    setError(null);
                  }}
                  style={{ background: swatch(c) }}
                  aria-label={c}
                  className={`h-9 w-9 rounded-pill border-2 ${
                    c === color ? 'border-primary' : 'border-hairline'
                  }`}
                />
              ))}
            </div>
          </div>
        )}

        <div className="mt-5">
          <p className="label">الكمية</p>
          <div className="flex w-fit items-center gap-3 rounded-pill border border-hairline px-2 py-1">
            <button
              onClick={() => setQuantity((q) => Math.max(1, q - 1))}
              className="rounded-pill p-1.5 text-ink hover:bg-surface-variant"
              aria-label="إنقاص"
            >
              <Minus size={15} />
            </button>
            <span className="w-6 text-center text-body font-semibold">
              {quantity}
            </span>
            <button
              onClick={() => setQuantity((q) => q + 1)}
              className="rounded-pill p-1.5 text-ink hover:bg-surface-variant"
              aria-label="زيادة"
            >
              <Plus size={15} />
            </button>
          </div>
        </div>

        {product.description && (
          <p className="mt-5 whitespace-pre-line text-body leading-relaxed text-muted">
            {product.description}
          </p>
        )}

        {error && <p className="field-error mt-4">{error}</p>}

        <button
          onClick={addToCart}
          disabled={addMutation.isPending}
          className="btn mt-5 w-full"
        >
          {added ? (
            <>
              <Check size={18} /> أُضيف للسلة
            </>
          ) : (
            <>
              <ShoppingBag size={18} /> أضف إلى السلة
            </>
          )}
        </button>
      </div>
    </div>
  );
}

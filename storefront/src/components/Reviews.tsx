import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Star } from 'lucide-react';
import { catalog, getErrorMessage } from '@/api';
import { useAuth } from '@/store/auth';
import { useLocale } from '@/store/locale';
import { relativeTime } from '@/lib/format';

function Stars({
  value,
  onPick,
}: {
  value: number;
  onPick?: (rating: number) => void;
}) {
  return (
    <span className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((n) => {
        const filled = n <= value;
        const star = (
          <Star
            size={15}
            className={filled ? 'fill-warning text-warning' : 'text-hint'}
          />
        );
        return onPick ? (
          <button
            key={n}
            type="button"
            onClick={() => onPick(n)}
            aria-label={`${n}`}
          >
            {star}
          </button>
        ) : (
          <span key={n}>{star}</span>
        );
      })}
    </span>
  );
}

export function Reviews({ productId }: { productId: string }) {
  const { t, locale } = useLocale();
  const navigate = useNavigate();
  const qc = useQueryClient();
  const authed = useAuth((s) => Boolean(s.token));

  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [writing, setWriting] = useState(false);

  const query = useQuery({
    queryKey: ['reviews', productId],
    queryFn: () => catalog.reviews(productId),
  });

  const submit = useMutation({
    mutationFn: () => catalog.addReview(productId, { rating, comment: comment.trim() || null }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['reviews', productId] });
      qc.invalidateQueries({ queryKey: ['product', productId] });
      setComment('');
      setRating(5);
      setWriting(false);
      setError(null);
    },
    onError: (e) => setError(getErrorMessage(e)),
  });

  const reviews = query.data ?? [];

  return (
    <section className="mt-8 border-t border-divider pt-6">
      <div className="mb-3 flex items-center justify-between">
        <h2 className="text-title font-bold text-ink">
          {t('reviews')}{' '}
          {reviews.length > 0 && (
            <span className="text-body text-muted">({reviews.length})</span>
          )}
        </h2>
        <button
          className="text-body font-semibold text-accent"
          onClick={() =>
            authed
              ? setWriting((v) => !v)
              : navigate('/login', { state: { from: `/p/${productId}` } })
          }
        >
          {t('write_review')}
        </button>
      </div>

      {writing && (
        <form
          className="card mb-4 space-y-3 p-4"
          onSubmit={(e) => {
            e.preventDefault();
            setError(null);
            submit.mutate();
          }}
        >
          <Stars value={rating} onPick={setRating} />
          <textarea
            className="field min-h-24"
            maxLength={2000}
            value={comment}
            onChange={(e) => setComment(e.target.value)}
          />
          {error && <p className="field-error">{error}</p>}
          <button className="btn btn--sm w-full" disabled={submit.isPending}>
            {t('submit')}
          </button>
        </form>
      )}

      {reviews.length === 0 ? (
        <p className="text-body text-muted">{t('no_reviews')}</p>
      ) : (
        <ul className="space-y-3">
          {reviews.map((r) => (
            <li key={r.id} className="card p-4">
              <div className="flex items-center justify-between">
                <span className="text-body font-semibold text-ink">
                  {r.author_name ?? '—'}
                </span>
                <Stars value={r.rating} />
              </div>
              {r.comment && (
                <p className="mt-1 text-body text-muted">{r.comment}</p>
              )}
              <p className="mt-1 text-caption text-hint">
                {relativeTime(r.created_at, locale)}
              </p>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}

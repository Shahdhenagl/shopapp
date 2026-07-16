import { Loader2 } from 'lucide-react';
import { useLocale } from '@/store/locale';

export function Loading({ label }: { label?: string }) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 py-16 text-muted">
      <Loader2 className="animate-spin" size={22} />
      {label && <p className="text-body">{label}</p>}
    </div>
  );
}

export function ErrorState({
  message,
  onRetry,
}: {
  message: string;
  onRetry?: () => void;
}) {
  const t = useLocale((s) => s.t);

  return (
    <div className="flex flex-col items-center justify-center gap-3 py-16">
      <p className="text-body text-danger">{message}</p>
      {onRetry && (
        <button className="btn btn--outlined btn--sm" onClick={onRetry}>
          {t('retry')}
        </button>
      )}
    </div>
  );
}

export function Empty({ label }: { label?: string }) {
  const t = useLocale((s) => s.t);

  return (
    <p className="py-16 text-center text-body text-muted">
      {label ?? t('nothing_here')}
    </p>
  );
}

/** Neutral skeleton block — surfaceVariant, per the app's loading style. */
export function Skeleton({ className = '' }: { className?: string }) {
  return (
    <div className={`animate-pulse rounded-card bg-surface-variant ${className}`} />
  );
}

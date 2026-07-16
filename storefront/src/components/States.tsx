import { Loader2 } from 'lucide-react';

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
  return (
    <div className="flex flex-col items-center justify-center gap-3 py-16">
      <p className="text-body text-danger">{message}</p>
      {onRetry && (
        <button className="btn btn--outlined btn--sm" onClick={onRetry}>
          إعادة المحاولة
        </button>
      )}
    </div>
  );
}

export function Empty({ label = 'لا يوجد شيء هنا بعد.' }: { label?: string }) {
  return <p className="py-16 text-center text-body text-muted">{label}</p>;
}

/** Neutral skeleton block — surfaceVariant, per the app's loading style. */
export function Skeleton({ className = '' }: { className?: string }) {
  return (
    <div className={`animate-pulse rounded-card bg-surface-variant ${className}`} />
  );
}

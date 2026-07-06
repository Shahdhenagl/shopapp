import { AlertCircle, Inbox, Loader2 } from 'lucide-react';
import { useLocaleStore } from '@/store/locale';
import { Button } from './Button';

export function LoadingState({ label }: { label?: string }) {
  const t = useLocaleStore((s) => s.t);
  return (
    <div className="flex flex-col items-center justify-center gap-3 py-16 text-slate-400">
      <Loader2 className="animate-spin" size={32} />
      <p className="text-sm">{label ?? t('loading')}</p>
    </div>
  );
}

export function EmptyState({ label }: { label?: string }) {
  const t = useLocaleStore((s) => s.t);
  return (
    <div className="flex flex-col items-center justify-center gap-3 py-16 text-slate-400">
      <Inbox size={36} />
      <p className="text-sm">{label ?? t('no_data')}</p>
    </div>
  );
}

export function ErrorState({
  message,
  onRetry,
}: {
  message?: string;
  onRetry?: () => void;
}) {
  const t = useLocaleStore((s) => s.t);
  return (
    <div className="flex flex-col items-center justify-center gap-3 py-16 text-red-500">
      <AlertCircle size={36} />
      <p className="text-sm">{message ?? t('error_generic')}</p>
      {onRetry && (
        <Button variant="outline" size="sm" onClick={onRetry}>
          Retry
        </Button>
      )}
    </div>
  );
}

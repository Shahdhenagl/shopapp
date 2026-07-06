import { CheckCircle2, Info, X, XCircle } from 'lucide-react';
import { clsx } from 'clsx';
import { useToastStore, type ToastVariant } from '@/store/toast';

const config: Record<ToastVariant, { icon: typeof Info; cls: string }> = {
  success: { icon: CheckCircle2, cls: 'border-green-500 text-green-700 dark:text-green-300' },
  error: { icon: XCircle, cls: 'border-red-500 text-red-700 dark:text-red-300' },
  info: { icon: Info, cls: 'border-blue-500 text-blue-700 dark:text-blue-300' },
};

export function ToastContainer() {
  const { toasts, dismiss } = useToastStore();
  return (
    <div className="fixed bottom-4 end-4 z-[100] flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2">
      {toasts.map((t) => {
        const { icon: Icon, cls } = config[t.variant];
        return (
          <div
            key={t.id}
            className={clsx(
              'card flex items-start gap-3 border-s-4 p-3 shadow-lg',
              cls,
            )}
          >
            <Icon size={20} className="mt-0.5 shrink-0" />
            <p className="flex-1 text-sm text-slate-700 dark:text-slate-200">
              {t.message}
            </p>
            <button
              onClick={() => dismiss(t.id)}
              className="text-slate-400 hover:text-slate-700"
              aria-label="Dismiss"
            >
              <X size={16} />
            </button>
          </div>
        );
      })}
    </div>
  );
}

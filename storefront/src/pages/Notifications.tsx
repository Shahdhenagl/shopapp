import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Bell, CheckCheck, Package, Sparkles, Tag } from 'lucide-react';
import { getErrorMessage, notifications } from '@/api';
import { Empty, ErrorState, Loading } from '@/components/States';
import { useLocale } from '@/store/locale';
import { relativeTime } from '@/lib/format';
import type { AppNotification, NotificationType } from '@/types';

const TYPE_UI: Record<NotificationType, { icon: typeof Bell; chip: string }> = {
  order: { icon: Package, chip: 'chip--success' },
  promo: { icon: Tag, chip: 'chip--error' }, // the app tints promo with error
  product: { icon: Sparkles, chip: 'chip--sale' },
  general: { icon: Bell, chip: '' },
};

export function Notifications() {
  const { t, locale } = useLocale();
  const qc = useQueryClient();

  const query = useQuery({
    queryKey: ['notifications'],
    queryFn: () => notifications.list(),
  });

  const markRead = useMutation({
    mutationFn: () => notifications.markAllRead(),
    // Adopt the server's returned list wholesale, as the app does.
    onSuccess: (list) => qc.setQueryData(['notifications'], list),
  });

  const items = query.data ?? [];
  const hasUnread = items.some((n: AppNotification) => !n.is_read);

  return (
    <div className="mx-auto max-w-2xl">
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-title font-bold text-ink">{t('notifications')}</h1>
        {hasUnread && (
          <button
            onClick={() => markRead.mutate()}
            disabled={markRead.isPending}
            className="flex items-center gap-1.5 rounded-pill px-3 py-2 text-caption font-semibold text-accent hover:bg-surface-variant"
          >
            <CheckCheck size={15} /> {t('mark_all_read')}
          </button>
        )}
      </div>

      {query.isLoading ? (
        <Loading />
      ) : query.error ? (
        <ErrorState
          message={getErrorMessage(query.error)}
          onRetry={() => query.refetch()}
        />
      ) : items.length === 0 ? (
        <Empty label={t('no_notifications')} />
      ) : (
        <ul className="space-y-2">
          {items.map((n) => {
            const ui = TYPE_UI[n.type] ?? TYPE_UI.general;
            const Icon = ui.icon;
            return (
              <li
                key={n.id}
                className={`card flex gap-3 p-4 ${
                  n.is_read ? '' : 'border-primary/40 bg-info-surface'
                }`}
              >
                <span className={`chip ${ui.chip} h-fit`}>
                  <Icon size={13} />
                </span>
                <div className="min-w-0 flex-1">
                  <p className="text-body text-ink">{n.message}</p>
                  <p className="mt-1 text-caption text-hint">
                    {relativeTime(n.created_at, locale)}
                  </p>
                  {n.images.length > 0 && (
                    <div className="mt-2 flex gap-2">
                      {n.images.map((src) => (
                        <img
                          key={src}
                          src={src}
                          alt=""
                          className="h-14 w-14 rounded-input object-cover"
                        />
                      ))}
                    </div>
                  )}
                </div>
                {!n.is_read && (
                  <span className="mt-1 h-2 w-2 flex-none rounded-pill bg-primary" />
                )}
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
}

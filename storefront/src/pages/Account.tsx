import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  Bell,
  ChevronLeft,
  Heart,
  LogOut,
  MapPin,
  Package,
  Pencil,
  Settings as SettingsIcon,
  ShieldCheck,
} from 'lucide-react';
import { auth, getErrorMessage, notifications } from '@/api';
import { useAuth } from '@/store/auth';
import { useLocale } from '@/store/locale';

/** Soft nudge — the account works unverified; only checkout is gated (server-side). */
function VerifyEmailBanner() {
  const t = useLocale((s) => s.t);
  const qc = useQueryClient();
  const [code, setCode] = useState('');
  const [sent, setSent] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const send = useMutation({
    mutationFn: () => auth.sendEmailCode(),
    onSuccess: () => {
      setSent(true);
      setError(null);
    },
    onError: (e) => setError(getErrorMessage(e)),
  });

  const verify = useMutation({
    mutationFn: () => auth.verifyEmail(code.trim()),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['me'] }),
    onError: (e) => setError(getErrorMessage(e)),
  });

  return (
    <div className="card mb-4 border-warning/40 bg-warning-surface p-4">
      <div className="flex items-start gap-3">
        <ShieldCheck size={18} className="mt-0.5 flex-none text-warning" />
        <div className="min-w-0 flex-1">
          <p className="text-body font-semibold text-ink">
            {t('verify_email_title')}
          </p>
          <p className="text-caption text-muted">{t('verify_email_body')}</p>

          {sent ? (
            <div className="mt-3 flex gap-2">
              <input
                className="field py-2"
                dir="ltr"
                placeholder={t('code')}
                value={code}
                onChange={(e) => setCode(e.target.value)}
              />
              <button
                className="btn btn--sm"
                disabled={!code.trim() || verify.isPending}
                onClick={() => verify.mutate()}
              >
                {t('verify')}
              </button>
            </div>
          ) : (
            <button
              className="btn btn--outlined btn--sm mt-3"
              disabled={send.isPending}
              onClick={() => send.mutate()}
            >
              {t('send_code')}
            </button>
          )}

          {error && <p className="field-error">{error}</p>}
        </div>
      </div>
    </div>
  );
}

function Row({
  to,
  icon: Icon,
  label,
  badge,
}: {
  to: string;
  icon: typeof Package;
  label: string;
  badge?: number;
}) {
  return (
    <Link
      to={to}
      className="flex items-center gap-3 px-4 py-3.5 transition hover:bg-surface-variant"
    >
      <Icon size={17} className="flex-none text-muted" />
      <span className="flex-1 text-body text-ink">{label}</span>
      {badge !== undefined && badge > 0 && (
        <span className="rounded-pill bg-primary px-2 py-0.5 text-[10px] font-bold text-on-primary">
          {badge}
        </span>
      )}
      <ChevronLeft size={16} className="flex-none text-hint rtl:rotate-0" />
    </Link>
  );
}

export function Account() {
  const navigate = useNavigate();
  const t = useLocale((s) => s.t);
  const { user, setUser, clear } = useAuth();

  const meQuery = useQuery({ queryKey: ['me'], queryFn: () => auth.me() });
  const notificationsQuery = useQuery({
    queryKey: ['notifications'],
    queryFn: () => notifications.list(),
  });

  useEffect(() => {
    if (meQuery.data) setUser(meQuery.data);
  }, [meQuery.data, setUser]);

  const signOut = async () => {
    try {
      await auth.logout();
    } catch {
      // The local session goes either way — the app swallows this too.
    }
    clear();
    navigate('/');
  };

  const profile = meQuery.data ?? user;
  const unread = (notificationsQuery.data ?? []).filter((n) => !n.is_read).length;

  return (
    <div className="mx-auto max-w-md">
      {profile?.email_verified === false && <VerifyEmailBanner />}

      {/* Identity */}
      <div className="card mb-4 flex items-center gap-3 p-4">
        {profile?.avatar_url ? (
          <img
            src={profile.avatar_url}
            alt=""
            className="h-14 w-14 rounded-pill object-cover"
          />
        ) : (
          <span className="grid h-14 w-14 place-items-center rounded-pill bg-surface-variant text-title font-bold text-muted">
            {profile?.name?.[0] ?? '·'}
          </span>
        )}
        <div className="min-w-0 flex-1">
          <p className="truncate text-body font-bold text-ink">
            {profile?.name ?? '—'}
          </p>
          <p className="truncate text-caption text-muted">{profile?.email}</p>
        </div>
        <Link
          to="/account/edit"
          aria-label={t('edit_profile')}
          className="rounded-pill p-2 text-muted hover:bg-surface-variant"
        >
          <Pencil size={16} />
        </Link>
      </div>

      {/* Menu */}
      <nav className="card divide-y divide-divider overflow-hidden p-0">
        <Row to="/orders" icon={Package} label={t('orders')} />
        <Row to="/addresses" icon={MapPin} label={t('addresses')} />
        <Row
          to="/notifications"
          icon={Bell}
          label={t('notifications')}
          badge={unread}
        />
        <Row to="/favorites" icon={Heart} label={t('favorites')} />
        <Row to="/settings" icon={SettingsIcon} label={t('settings')} />
      </nav>

      <button
        onClick={signOut}
        className="mt-4 flex w-full items-center justify-center gap-2 rounded-btn border border-hairline py-3.5 text-body font-semibold text-danger transition hover:bg-danger-surface"
      >
        <LogOut size={16} /> {t('sign_out')}
      </button>
    </div>
  );
}

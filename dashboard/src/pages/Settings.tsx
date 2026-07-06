import { useQuery } from '@tanstack/react-query';
import { Globe, Heart, Server } from 'lucide-react';
import { favoritesService, getErrorMessage, profileService } from '@/api';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/Badge';
import { LoadingState } from '@/components/States';
import { useLocaleStore } from '@/store/locale';
import { API_BASE_URL, USE_MOCK } from '@/lib/config';

export function Settings() {
  const { t, locale, setLocale } = useLocaleStore();

  const profileQuery = useQuery({
    queryKey: ['me'],
    queryFn: () => profileService.me(),
  });

  const favoritesQuery = useQuery({
    queryKey: ['favorites'],
    queryFn: () => favoritesService.list(),
  });

  return (
    <div>
      <PageHeader title={t('nav_settings')} subtitle="Profile, favorites & environment" />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div className="card p-5">
          <h3 className="mb-4 font-semibold">Profile (GET /me)</h3>
          {profileQuery.isLoading ? (
            <LoadingState />
          ) : profileQuery.error ? (
            <p className="text-sm text-red-500">{getErrorMessage(profileQuery.error)}</p>
          ) : profileQuery.data ? (
            <div className="space-y-2 text-sm">
              <Row label="Name" value={profileQuery.data.name} />
              <Row label="Email" value={profileQuery.data.email} />
              <Row label="Phone" value={profileQuery.data.phone ?? '—'} />
            </div>
          ) : null}
        </div>

        <div className="card p-5">
          <h3 className="mb-4 flex items-center gap-2 font-semibold">
            <Heart size={18} /> Favorites
          </h3>
          {favoritesQuery.isLoading ? (
            <LoadingState />
          ) : (
            <div className="flex flex-wrap gap-2">
              {(favoritesQuery.data ?? []).length === 0 ? (
                <p className="text-sm text-slate-400">No favorites</p>
              ) : (
                (favoritesQuery.data ?? []).map((id) => (
                  <Badge key={id} tone="purple">
                    {id}
                  </Badge>
                ))
              )}
            </div>
          )}
        </div>

        <div className="card p-5">
          <h3 className="mb-4 flex items-center gap-2 font-semibold">
            <Globe size={18} /> Language
          </h3>
          <div className="flex gap-2">
            {(['en', 'ar'] as const).map((l) => (
              <button
                key={l}
                onClick={() => setLocale(l)}
                className={`rounded-lg border px-4 py-2 text-sm font-medium ${
                  locale === l
                    ? 'border-brand-700 bg-brand-700 text-white'
                    : 'border-slate-300 dark:border-slate-700'
                }`}
              >
                {l === 'en' ? 'English' : 'العربية'}
              </button>
            ))}
          </div>
        </div>

        <div className="card p-5">
          <h3 className="mb-4 flex items-center gap-2 font-semibold">
            <Server size={18} /> Environment
          </h3>
          <div className="space-y-2 text-sm">
            <Row
              label="Data source"
              value={
                <Badge tone={USE_MOCK ? 'yellow' : 'green'}>
                  {USE_MOCK ? 'MOCK' : 'LIVE API'}
                </Badge>
              }
            />
            <Row label="API base URL" value={<span className="font-mono">{API_BASE_URL}</span>} />
            <p className="pt-2 text-xs text-slate-400">
              Set <code className="font-mono">VITE_USE_MOCK=false</code> in your
              .env to hit the real Laravel backend.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="flex items-center justify-between border-b border-slate-100 py-1.5 last:border-0 dark:border-slate-800">
      <span className="text-slate-500">{label}</span>
      <span>{value}</span>
    </div>
  );
}

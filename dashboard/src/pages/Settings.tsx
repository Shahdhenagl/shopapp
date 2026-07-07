import { useRef, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Globe, Server, Store, Upload } from 'lucide-react';
import {
  getErrorMessage,
  settingsService,
  uploadMedia,
} from '@/api';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/Badge';
import { Button } from '@/components/Button';
import { LoadingState, ErrorState } from '@/components/States';
import { useLocaleStore } from '@/store/locale';
import { toast } from '@/store/toast';
import { hexArgbToCss } from '@/lib/format';
import { ADMIN_API_BASE_URL, USE_MOCK } from '@/lib/config';
import type {
  StorefrontMode,
  StoreSettings,
  StoreSettingsFlags,
  StoreSettingsUpdate,
} from '@/types';

const FLAG_LABELS: Record<keyof StoreSettingsFlags, string> = {
  card_payment: 'Card payment',
  cash_payment: 'Cash payment',
  promo_codes: 'Promo codes',
  favorites: 'Favorites',
};

export function Settings() {
  const { t, locale, setLocale } = useLocaleStore();
  const query = useQuery({
    queryKey: ['settings'],
    queryFn: () => settingsService.get(),
  });

  return (
    <div>
      <PageHeader
        title={t('nav_settings')}
        subtitle="Store identity, storefront mode, branding & feature flags"
      />

      <div className="grid grid-cols-1 gap-6">
        <div className="card p-5">
          {query.isLoading ? (
            <LoadingState />
          ) : query.error || !query.data ? (
            <ErrorState
              message={getErrorMessage(query.error)}
              onRetry={() => query.refetch()}
            />
          ) : (
            <SettingsForm initial={query.data} />
          )}
        </div>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
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
              <Row
                label="Admin API base"
                value={<span className="font-mono">{ADMIN_API_BASE_URL}</span>}
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

function SettingsForm({ initial }: { initial: StoreSettings }) {
  const qc = useQueryClient();
  const fileRef = useRef<HTMLInputElement>(null);
  const [form, setForm] = useState<StoreSettings>(initial);
  const [uploading, setUploading] = useState(false);

  const mutation = useMutation({
    mutationFn: (patch: StoreSettingsUpdate) => settingsService.update(patch),
    onSuccess: (data) => {
      qc.setQueryData(['settings'], data);
      qc.invalidateQueries({ queryKey: ['settings'] });
      setForm(data);
      toast.success('Settings saved');
    },
    onError: (e) => toast.error(getErrorMessage(e)),
  });

  const set = <K extends keyof StoreSettings>(key: K, value: StoreSettings[K]) =>
    setForm((prev) => ({ ...prev, [key]: value }));

  const setBrand = (key: keyof StoreSettings['brand'], value: string) =>
    setForm((prev) => ({ ...prev, brand: { ...prev.brand, [key]: value } }));

  const setFlag = (key: keyof StoreSettingsFlags, value: boolean) =>
    setForm((prev) => ({ ...prev, flags: { ...prev.flags, [key]: value } }));

  const onPickLogo = async (file: File | undefined) => {
    if (!file) return;
    setUploading(true);
    try {
      const url = await uploadMedia(file);
      set('logo_url', url);
      toast.success('Logo uploaded');
    } catch (e) {
      toast.error(getErrorMessage(e));
    } finally {
      setUploading(false);
    }
  };

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const patch: StoreSettingsUpdate = {
      app_name: form.app_name,
      currency: form.currency,
      storefront_mode: form.storefront_mode,
      logo_url: form.logo_url,
      shipping_fee: Number(form.shipping_fee),
      brand_primary: form.brand.primary,
      brand_on_primary: form.brand.on_primary,
      brand_accent: form.brand.accent,
      flags: form.flags,
    };
    mutation.mutate(patch);
  };

  return (
    <form onSubmit={onSubmit} className="space-y-8">
      {/* Identity */}
      <section className="space-y-4">
        <h3 className="flex items-center gap-2 font-semibold">
          <Store size={18} /> Identity
        </h3>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="label">App name</label>
            <input
              className="input"
              value={form.app_name}
              onChange={(e) => set('app_name', e.target.value)}
            />
          </div>
          <div>
            <label className="label">Currency</label>
            <input
              className="input"
              value={form.currency}
              onChange={(e) => set('currency', e.target.value)}
            />
          </div>
        </div>
        <div>
          <label className="label">Logo</label>
          <div className="flex items-center gap-3">
            {form.logo_url ? (
              <img
                src={form.logo_url}
                alt="logo"
                className="h-14 w-14 rounded-lg border border-slate-200 object-cover dark:border-slate-700"
              />
            ) : (
              <div className="flex h-14 w-14 items-center justify-center rounded-lg border border-dashed border-slate-300 text-xs text-slate-400 dark:border-slate-700">
                None
              </div>
            )}
            <input
              ref={fileRef}
              type="file"
              accept="image/*"
              className="hidden"
              onChange={(e) => onPickLogo(e.target.files?.[0])}
            />
            <Button
              type="button"
              variant="outline"
              size="sm"
              loading={uploading}
              icon={<Upload size={16} />}
              onClick={() => fileRef.current?.click()}
            >
              Upload logo
            </Button>
            {form.logo_url && (
              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={() => set('logo_url', null)}
              >
                Remove
              </Button>
            )}
          </div>
        </div>
      </section>

      {/* Storefront mode */}
      <section className="space-y-3">
        <h3 className="flex items-center gap-2 font-semibold">
          <span aria-hidden>⭐</span> Storefront mode
        </h3>
        <div className="inline-flex rounded-lg border border-slate-300 p-1 dark:border-slate-700">
          {(
            [
              ['single', 'Single department'],
              ['multi_department', 'Multi-department'],
            ] as [StorefrontMode, string][]
          ).map(([value, label]) => (
            <button
              key={value}
              type="button"
              onClick={() => set('storefront_mode', value)}
              className={`rounded-md px-4 py-1.5 text-sm font-medium transition ${
                form.storefront_mode === value
                  ? 'bg-brand-700 text-white'
                  : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
              }`}
            >
              {label}
            </button>
          ))}
        </div>
        <p className="text-xs text-slate-400">
          Multi-department requires a category tree with department nodes.
        </p>
      </section>

      {/* Branding */}
      <section className="space-y-3">
        <h3 className="font-semibold">Branding</h3>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <ColorField
            label="Primary"
            value={form.brand.primary}
            onChange={(v) => setBrand('primary', v)}
          />
          <ColorField
            label="On primary"
            value={form.brand.on_primary}
            onChange={(v) => setBrand('on_primary', v)}
          />
          <ColorField
            label="Accent"
            value={form.brand.accent}
            onChange={(v) => setBrand('accent', v)}
          />
        </div>
      </section>

      {/* Shipping + flags */}
      <section className="space-y-4">
        <h3 className="font-semibold">Commerce</h3>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="label">Shipping fee</label>
            <input
              type="number"
              step="0.01"
              min="0"
              className="input"
              value={form.shipping_fee}
              onChange={(e) => set('shipping_fee', Number(e.target.value))}
            />
          </div>
        </div>
        <div>
          <label className="label">Feature flags</label>
          <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
            {(Object.keys(FLAG_LABELS) as (keyof StoreSettingsFlags)[]).map(
              (key) => (
                <label
                  key={key}
                  className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700"
                >
                  <input
                    type="checkbox"
                    className="h-4 w-4 rounded"
                    checked={form.flags[key]}
                    onChange={(e) => setFlag(key, e.target.checked)}
                  />
                  {FLAG_LABELS[key]}
                </label>
              ),
            )}
          </div>
        </div>
      </section>

      <div className="flex justify-end">
        <Button type="submit" loading={mutation.isPending}>
          Save settings
        </Button>
      </div>
    </form>
  );
}

function ColorField({
  label,
  value,
  onChange,
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
}) {
  return (
    <div>
      <label className="label">{label}</label>
      <div className="flex items-center gap-2">
        <input
          type="color"
          value={hexArgbToCss(value)}
          onChange={(e) => onChange(e.target.value.toUpperCase())}
          className="h-9 w-10 cursor-pointer rounded"
        />
        <input
          className="input flex-1 font-mono text-xs"
          value={value}
          onChange={(e) => onChange(e.target.value)}
        />
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

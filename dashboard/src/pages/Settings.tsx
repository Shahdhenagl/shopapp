import { useMemo, useRef, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  ArrowDown,
  ArrowUp,
  Globe,
  LayoutGrid,
  Plus,
  Server,
  Store,
  Upload,
  X,
} from 'lucide-react';
import {
  adminCategoriesService,
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
  CategoryNode,
  Locale,
  StorefrontMode,
  StoreSettings,
  StoreSettingsFlags,
  StoreSettingsUpdate,
} from '@/types';

// Flatten the category tree into pickable options. `id` sent to the API is the
// slug (the public GET /categories wire id the app resolves rails against).
// Departments are prefixed by depth so nesting is readable in the picker.
interface RailOption {
  slug: string;
  label: string;
}

function flattenCategories(
  nodes: CategoryNode[],
  locale: Locale,
  depth = 0,
): RailOption[] {
  const out: RailOption[] = [];
  for (const node of nodes) {
    const name = node.name?.[locale] || node.name?.en || node.slug;
    out.push({ slug: node.slug, label: `${'— '.repeat(depth)}${name}` });
    if (node.children?.length) {
      out.push(...flattenCategories(node.children, locale, depth + 1));
    }
  }
  return out;
}

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
  const { locale } = useLocaleStore();
  const fileRef = useRef<HTMLInputElement>(null);
  const [form, setForm] = useState<StoreSettings>(initial);
  const [uploading, setUploading] = useState(false);

  // Category options for the Home rails picker.
  const categoriesQuery = useQuery({
    queryKey: ['categories', 'tree'],
    queryFn: () => adminCategoriesService.tree(),
  });
  const railOptions = useMemo(
    () => flattenCategories(categoriesQuery.data ?? [], locale),
    [categoriesQuery.data, locale],
  );

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

  // --- Home rails helpers ---------------------------------------------------
  const rails = form.home_rail_categories;
  const atCap = rails.length >= form.max_home_rails;
  const labelForSlug = (slug: string) =>
    railOptions.find((o) => o.slug === slug)?.label.replace(/^(— )+/, '') ??
    slug;
  const availableOptions = railOptions.filter((o) => !rails.includes(o.slug));

  const addRail = (slug: string) => {
    if (!slug || rails.includes(slug) || atCap) return;
    set('home_rail_categories', [...rails, slug]);
  };
  const removeRail = (slug: string) =>
    set(
      'home_rail_categories',
      rails.filter((s) => s !== slug),
    );
  const moveRail = (index: number, dir: -1 | 1) => {
    const next = [...rails];
    const target = index + dir;
    if (target < 0 || target >= next.length) return;
    [next[index], next[target]] = [next[target], next[index]];
    set('home_rail_categories', next);
  };

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
    // Drop stale rail ids (categories deleted since they were picked). Only
    // filter once the tree has loaded, so a slow query never wipes the list.
    const knownSlugs = new Set(railOptions.map((o) => o.slug));
    const railCategories = categoriesQuery.data
      ? form.home_rail_categories.filter((s) => knownSlugs.has(s))
      : form.home_rail_categories;

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
      home_rail_categories: railCategories.slice(0, form.max_home_rails),
      max_home_rails: form.max_home_rails,
      home_rail_item_count: form.home_rail_item_count,
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

      {/* Home rails */}
      <section className="space-y-4">
        <div>
          <h3 className="flex items-center gap-2 font-semibold">
            <LayoutGrid size={18} /> Home rails
          </h3>
          <p className="mt-1 text-xs text-slate-400">
            Promote categories as “newest” rails on the app Home screen. Drag
            order = display order. Empty rails (no products) are hidden in the
            app automatically.
          </p>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="label">Max rails (0–20)</label>
            <input
              type="number"
              min={0}
              max={20}
              className="input"
              value={form.max_home_rails}
              onChange={(e) =>
                set(
                  'max_home_rails',
                  Math.max(0, Math.min(20, Number(e.target.value) || 0)),
                )
              }
            />
            <p className="mt-1 text-xs text-slate-400">0 = feature off.</p>
          </div>
          <div>
            <label className="label">Products per rail (1–20)</label>
            <input
              type="number"
              min={1}
              max={20}
              className="input"
              value={form.home_rail_item_count}
              onChange={(e) =>
                set(
                  'home_rail_item_count',
                  Math.max(1, Math.min(20, Number(e.target.value) || 1)),
                )
              }
            />
          </div>
        </div>

        <div>
          <div className="mb-2 flex items-center justify-between">
            <label className="label mb-0">
              Promoted categories ({rails.length}/{form.max_home_rails})
            </label>
          </div>

          {/* Selected, ordered list */}
          {rails.length === 0 ? (
            <p className="rounded-lg border border-dashed border-slate-300 px-3 py-4 text-center text-sm text-slate-400 dark:border-slate-700">
              No rails yet. Add a category below.
            </p>
          ) : (
            <ul className="space-y-2">
              {rails.map((slug, i) => (
                <li
                  key={slug}
                  className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700"
                >
                  <span className="w-5 text-center text-xs text-slate-400">
                    {i + 1}
                  </span>
                  <span className="flex-1 truncate">{labelForSlug(slug)}</span>
                  <button
                    type="button"
                    className="rounded p-1 text-slate-400 hover:bg-slate-100 disabled:opacity-30 dark:hover:bg-slate-800"
                    disabled={i === 0}
                    onClick={() => moveRail(i, -1)}
                    aria-label="Move up"
                  >
                    <ArrowUp size={15} />
                  </button>
                  <button
                    type="button"
                    className="rounded p-1 text-slate-400 hover:bg-slate-100 disabled:opacity-30 dark:hover:bg-slate-800"
                    disabled={i === rails.length - 1}
                    onClick={() => moveRail(i, 1)}
                    aria-label="Move down"
                  >
                    <ArrowDown size={15} />
                  </button>
                  <button
                    type="button"
                    className="rounded p-1 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950"
                    onClick={() => removeRail(slug)}
                    aria-label="Remove"
                  >
                    <X size={15} />
                  </button>
                </li>
              ))}
            </ul>
          )}

          {/* Add picker */}
          <div className="mt-3 flex items-center gap-2">
            <select
              className="input flex-1"
              value=""
              disabled={atCap || availableOptions.length === 0}
              onChange={(e) => {
                addRail(e.target.value);
                e.target.value = '';
              }}
            >
              <option value="">
                {atCap
                  ? `Max ${form.max_home_rails} rails reached`
                  : availableOptions.length === 0
                    ? 'All categories added'
                    : '+ Add a category…'}
              </option>
              {availableOptions.map((o) => (
                <option key={o.slug} value={o.slug}>
                  {o.label}
                </option>
              ))}
            </select>
            <Plus size={16} className="text-slate-400" />
          </div>
          {categoriesQuery.isError && (
            <p className="mt-2 text-xs text-rose-500">
              Couldn’t load categories — {getErrorMessage(categoriesQuery.error)}
            </p>
          )}
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
  value: string | null;
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
          placeholder="e.g. #0E0E0E"
          value={value ?? ''}
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

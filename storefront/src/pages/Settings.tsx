import { Monitor, Moon, Sun } from 'lucide-react';
import { useLocale } from '@/store/locale';
import { useTheme, type ThemeMode } from '@/store/theme';
import type { Locale } from '@/types';

// Local-only, exactly like the app's SettingsScreen: theme + language, nothing
// that touches the network.
export function Settings() {
  const { locale, setLocale, t } = useLocale();
  const { mode, setMode } = useTheme();

  const themes: { value: ThemeMode; icon: typeof Sun; label: string }[] = [
    { value: 'system', icon: Monitor, label: t('theme_system') },
    { value: 'light', icon: Sun, label: t('theme_light') },
    { value: 'dark', icon: Moon, label: t('theme_dark') },
  ];

  const locales: { value: Locale; label: string }[] = [
    { value: 'ar', label: 'العربية' },
    { value: 'en', label: 'English' },
  ];

  return (
    <div className="mx-auto max-w-md">
      <h1 className="mb-4 text-title font-bold text-ink">{t('settings')}</h1>

      <section className="card mb-4 p-4">
        <h2 className="mb-3 text-body font-bold text-ink">{t('appearance')}</h2>
        <div className="grid grid-cols-3 gap-2">
          {themes.map(({ value, icon: Icon, label }) => (
            <button
              key={value}
              type="button"
              onClick={() => setMode(value)}
              aria-pressed={mode === value}
              className={`flex flex-col items-center gap-1.5 rounded-btn border p-3 text-caption font-semibold transition ${
                mode === value
                  ? 'border-primary bg-primary text-on-primary'
                  : 'border-hairline text-ink hover:bg-surface-variant'
              }`}
            >
              <Icon size={18} />
              {label}
            </button>
          ))}
        </div>
      </section>

      <section className="card p-4">
        <h2 className="mb-3 text-body font-bold text-ink">{t('language')}</h2>
        <div className="grid grid-cols-2 gap-2">
          {locales.map(({ value, label }) => (
            <button
              key={value}
              type="button"
              onClick={() => setLocale(value)}
              aria-pressed={locale === value}
              className={`rounded-btn border p-3 text-body font-semibold transition ${
                locale === value
                  ? 'border-primary bg-primary text-on-primary'
                  : 'border-hairline text-ink hover:bg-surface-variant'
              }`}
            >
              {label}
            </button>
          ))}
        </div>
      </section>
    </div>
  );
}

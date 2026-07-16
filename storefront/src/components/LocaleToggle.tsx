import { useLocale } from '@/store/locale';
import type { Locale } from '@/types';

const OPTIONS: { value: Locale; short: string; label: string }[] = [
  { value: 'ar', short: 'ع', label: 'العربية' },
  { value: 'en', short: 'EN', label: 'English' },
];

/**
 * The locale also sets Accept-Language, so switching re-resolves product copy
 * from the server — not just the UI strings.
 */
export function LocaleToggle() {
  const { locale, setLocale } = useLocale();

  return (
    <div
      className="flex items-center gap-0.5 rounded-pill border border-hairline p-0.5"
      role="group"
      aria-label="Language"
    >
      {OPTIONS.map(({ value, short, label }) => (
        <button
          key={value}
          type="button"
          onClick={() => setLocale(value)}
          title={label}
          aria-label={label}
          aria-pressed={locale === value}
          className={`min-w-7 rounded-pill px-1.5 py-1 text-caption font-bold transition ${
            locale === value
              ? 'bg-primary text-on-primary'
              : 'text-muted hover:text-ink'
          }`}
        >
          {short}
        </button>
      ))}
    </div>
  );
}

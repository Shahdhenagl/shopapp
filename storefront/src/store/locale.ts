import { create } from 'zustand';
import { translate } from '@/lib/i18n';
import type { Locale } from '@/types';

const KEY = 'modist_locale';

/**
 * The API resolves copy from Accept-Language, so the locale drives both the
 * UI strings and what the server sends back for names/descriptions.
 */
function apply(locale: Locale) {
  document.documentElement.lang = locale;
  document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
}

function initial(): Locale {
  const stored = localStorage.getItem(KEY) as Locale | null;
  if (stored === 'en' || stored === 'ar') return stored;
  return navigator.language.startsWith('en') ? 'en' : 'ar';
}

interface LocaleState {
  locale: Locale;
  setLocale: (locale: Locale) => void;
  t: (key: string, vars?: Record<string, string | number>) => string;
}

export const useLocale = create<LocaleState>((set, get) => {
  const locale = initial();
  apply(locale);

  return {
    locale,
    setLocale: (next) => {
      localStorage.setItem(KEY, next);
      apply(next);
      set({ locale: next });
    },
    t: (key, vars) => translate(get().locale, key, vars),
  };
});

import { create } from 'zustand';
import type { Locale } from '@/types';
import { LOCALE_STORAGE_KEY } from '@/lib/config';
import { translate } from '@/lib/i18n';

interface LocaleState {
  locale: Locale;
  dir: 'ltr' | 'rtl';
  setLocale: (locale: Locale) => void;
  toggleLocale: () => void;
  t: (key: string) => string;
}

function applyDir(locale: Locale): 'ltr' | 'rtl' {
  const dir = locale === 'ar' ? 'rtl' : 'ltr';
  document.documentElement.dir = dir;
  document.documentElement.lang = locale;
  return dir;
}

const stored = (localStorage.getItem(LOCALE_STORAGE_KEY) as Locale) || 'en';

export const useLocaleStore = create<LocaleState>((set, get) => ({
  locale: stored,
  dir: applyDir(stored),
  setLocale: (locale) => {
    localStorage.setItem(LOCALE_STORAGE_KEY, locale);
    set({ locale, dir: applyDir(locale) });
  },
  toggleLocale: () => {
    const next: Locale = get().locale === 'en' ? 'ar' : 'en';
    get().setLocale(next);
  },
  t: (key) => translate(get().locale, key),
}));

import { useLocale } from '@/store/locale';

/**
 * The API resolves names/descriptions from Accept-Language, so the same URL
 * returns different copy per language. Keying catalog queries by locale makes a
 * language switch a normal refetch — no cache clearing, which is what left the
 * first fetches pending and the page stuck loading.
 */
export function useCatalogKey(...parts: unknown[]): unknown[] {
  const locale = useLocale((s) => s.locale);
  return [...parts, locale];
}

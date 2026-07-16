export function money(amount: number, currency = 'EGP'): string {
  return `${amount.toLocaleString('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })} ${currency}`;
}

export function formatDate(iso: string): string {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
}

/**
 * Bucketed relative time — just-now / minutes / hours / days, matching the
 * app's RelativeTime.since.
 */
export function relativeTime(iso: string, locale: 'en' | 'ar' = 'ar'): string {
  const then = new Date(iso).getTime();
  if (Number.isNaN(then)) return iso;

  const seconds = Math.max(0, Math.floor((Date.now() - then) / 1000));
  const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });

  if (seconds < 60) return rtf.format(0, 'second'); // "now"
  if (seconds < 3600) return rtf.format(-Math.floor(seconds / 60), 'minute');
  if (seconds < 86_400) return rtf.format(-Math.floor(seconds / 3600), 'hour');
  return rtf.format(-Math.floor(seconds / 86_400), 'day');
}

/** #AARRGGBB (the API's colour format) → a CSS colour for swatches. */
export function swatch(hex: string): string {
  const h = hex.replace('#', '');
  if (h.length === 8) return `#${h.slice(2)}`;
  if (h.length === 6) return `#${h}`;
  return '#000000';
}

/** The cart takes colours as ARGB ints. */
export function colorToInt(hex: string): number {
  const parsed = Number.parseInt(hex.replace('#', ''), 16);
  return Number.isNaN(parsed) ? 0 : parsed;
}

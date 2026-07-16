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

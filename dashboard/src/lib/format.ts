export function formatMoney(amount: number, currency = 'EGP'): string {
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

// Convert an #AARRGGBB hex string into a CSS #RRGGBB(AA) for swatches.
export function hexArgbToCss(hex: string | null | undefined): string {
  // Brand colours are optional; fall back to a neutral so a null never crashes
  // a <input type="color"> (which requires a valid #RRGGBB).
  if (!hex) return '#000000';
  const h = hex.replace('#', '');
  if (h.length === 8) return `#${h.slice(2)}`;
  if (h.length === 6) return `#${h}`;
  return '#000000';
}

// Convert an int color value (ARGB) to an #AARRGGBB hex string.
export function intArgbToHex(value: number): string {
  return `#${(value >>> 0).toString(16).toUpperCase().padStart(8, '0')}`;
}

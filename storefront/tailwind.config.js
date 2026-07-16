/** @type {import('tailwindcss').Config} */
// Every colour/radius/size here points at a token from src/styles/modist-theme.css,
// which is generated from the Flutter app's AppPalette. Nothing is hard-coded:
// re-generating the tokens restyles the site, and light/dark resolve themselves
// through the CSS variables (no `dark:` variants needed for themed values).
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: 'var(--modist-primary)',
        'on-primary': 'var(--modist-on-primary)',
        canvas: 'var(--modist-background)',
        surface: 'var(--modist-surface)',
        'surface-variant': 'var(--modist-surface-variant)',
        field: 'var(--modist-field-fill)',
        ink: 'var(--modist-text-primary)',
        muted: 'var(--modist-text-muted)',
        hint: 'var(--modist-text-hint)',
        hairline: 'var(--modist-border)',
        divider: 'var(--modist-divider)',
        success: 'var(--modist-success)',
        'success-surface': 'var(--modist-success-surface)',
        danger: 'var(--modist-error)',
        'danger-surface': 'var(--modist-error-surface)',
        warning: 'var(--modist-warning)',
        'warning-surface': 'var(--modist-warning-surface)',
        'info-surface': 'var(--modist-info-surface)',
        accent: 'var(--modist-accent)',
        'section-fill': 'var(--modist-section-fill)',
        pink: 'var(--modist-accent-pink)',
        'pink-surface': 'var(--modist-accent-pink-surface)',
      },
      fontFamily: {
        sans: ['Cairo', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
      },
      fontSize: {
        title: 'var(--modist-text-title)',
        btn: 'var(--modist-text-button)',
        'btn-outlined': 'var(--modist-text-button-outlined)',
        body: 'var(--modist-text-body)',
        caption: 'var(--modist-text-caption)',
        nav: 'var(--modist-text-nav)',
      },
      borderRadius: {
        input: 'var(--modist-radius-input)',
        btn: 'var(--modist-radius-button)',
        card: 'var(--modist-radius-card)',
        pill: 'var(--modist-radius-pill)',
      },
      boxShadow: {
        nav: 'var(--modist-shadow-nav)',
      },
    },
  },
  plugins: [],
};

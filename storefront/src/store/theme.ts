import { create } from 'zustand';

export type ThemeMode = 'system' | 'light' | 'dark';

const KEY = 'modist_theme';

/**
 * The tokens resolve themselves: :root is light, the prefers-color-scheme
 * block covers system dark, and [data-theme] wins over both. So "system" is
 * simply the absence of the attribute — mirroring the app's ThemeCubit.
 */
function apply(mode: ThemeMode) {
  const root = document.documentElement;
  if (mode === 'system') root.removeAttribute('data-theme');
  else root.setAttribute('data-theme', mode);
}

function initial(): ThemeMode {
  const stored = localStorage.getItem(KEY) as ThemeMode | null;
  return stored ?? 'system';
}

interface ThemeState {
  mode: ThemeMode;
  setMode: (mode: ThemeMode) => void;
}

export const useTheme = create<ThemeState>((set) => {
  const mode = initial();
  apply(mode);

  return {
    mode,
    setMode: (next) => {
      localStorage.setItem(KEY, next);
      apply(next);
      set({ mode: next });
    },
  };
});

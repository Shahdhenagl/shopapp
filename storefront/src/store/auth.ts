import { create } from 'zustand';
import { REFRESH_KEY, TOKEN_KEY } from '@/lib/config';
import type { User } from '@/types';

interface AuthState {
  token: string | null;
  user: User | null;
  setSession: (token: string, user: User, refreshToken?: string) => void;
  setUser: (user: User) => void;
  clear: () => void;
  isAuthed: () => boolean;
}

export const useAuth = create<AuthState>((set, get) => ({
  token: localStorage.getItem(TOKEN_KEY),
  user: null,

  setSession: (token, user, refreshToken) => {
    localStorage.setItem(TOKEN_KEY, token);
    if (refreshToken) localStorage.setItem(REFRESH_KEY, refreshToken);
    set({ token, user });
  },

  setUser: (user) => set({ user }),

  clear: () => {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(REFRESH_KEY);
    set({ token: null, user: null });
  },

  isAuthed: () => Boolean(get().token),
}));

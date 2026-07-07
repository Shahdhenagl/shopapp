import { create } from 'zustand';
import type { AdminAuthResponse, AdminUser } from '@/types';
import { TOKEN_STORAGE_KEY, USER_STORAGE_KEY } from '@/lib/config';

interface AuthState {
  token: string | null;
  admin: AdminUser | null;
  isAuthenticated: boolean;
  setSession: (auth: AdminAuthResponse) => void;
  clearSession: () => void;
}

function readAdmin(): AdminUser | null {
  try {
    const raw = localStorage.getItem(USER_STORAGE_KEY);
    return raw ? (JSON.parse(raw) as AdminUser) : null;
  } catch {
    return null;
  }
}

const initialToken = localStorage.getItem(TOKEN_STORAGE_KEY);
const initialAdmin = readAdmin();

export const useAuthStore = create<AuthState>((set) => ({
  token: initialToken,
  admin: initialAdmin,
  isAuthenticated: Boolean(initialToken),
  setSession: (auth) => {
    localStorage.setItem(TOKEN_STORAGE_KEY, auth.token);
    localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(auth.admin));
    set({ token: auth.token, admin: auth.admin, isAuthenticated: true });
  },
  clearSession: () => {
    localStorage.removeItem(TOKEN_STORAGE_KEY);
    localStorage.removeItem(USER_STORAGE_KEY);
    set({ token: null, admin: null, isAuthenticated: false });
  },
}));

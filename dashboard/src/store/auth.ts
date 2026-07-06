import { create } from 'zustand';
import type { AuthResponse, User } from '@/types';
import { TOKEN_STORAGE_KEY, USER_STORAGE_KEY } from '@/lib/config';

interface AuthState {
  token: string | null;
  user: User | null;
  isAuthenticated: boolean;
  setSession: (auth: AuthResponse) => void;
  clearSession: () => void;
}

function readUser(): User | null {
  try {
    const raw = localStorage.getItem(USER_STORAGE_KEY);
    return raw ? (JSON.parse(raw) as User) : null;
  } catch {
    return null;
  }
}

const initialToken = localStorage.getItem(TOKEN_STORAGE_KEY);
const initialUser = readUser();

export const useAuthStore = create<AuthState>((set) => ({
  token: initialToken,
  user: initialUser,
  isAuthenticated: Boolean(initialToken),
  setSession: (auth) => {
    localStorage.setItem(TOKEN_STORAGE_KEY, auth.token);
    localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(auth.user));
    set({ token: auth.token, user: auth.user, isAuthenticated: true });
  },
  clearSession: () => {
    localStorage.removeItem(TOKEN_STORAGE_KEY);
    localStorage.removeItem(USER_STORAGE_KEY);
    set({ token: null, user: null, isAuthenticated: false });
  },
}));

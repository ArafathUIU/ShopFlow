import { create } from 'zustand';
import type { User } from '@/lib/types';
import { authService } from '@/lib/services/auth-service';

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  setUser: (user: User, token: string) => void;
  logout: () => Promise<void>;
  loadFromStorage: () => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  token: null,
  isAuthenticated: false,
  setUser: (user, token) =>
    set({
      user,
      token,
      isAuthenticated: true,
    }),
  logout: async () => {
    await authService.logout();
    set({
      user: null,
      token: null,
      isAuthenticated: false,
    });
  },
  loadFromStorage: () => {
    if (typeof window !== 'undefined') {
      const token = localStorage.getItem('auth_token');
      const userStr = localStorage.getItem('user');
      if (token && userStr) {
        try {
          const user = JSON.parse(userStr);
          set({
            user,
            token,
            isAuthenticated: true,
          });
        } catch {
          localStorage.removeItem('auth_token');
          localStorage.removeItem('user');
        }
      }
    }
  },
}));

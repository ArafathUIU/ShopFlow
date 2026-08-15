import { apiClient, type ApiResponse } from '../api-client';
import type { AuthResponse, User } from '../types';

export const authService = {
  async register(data: { name: string; email: string; password: string; password_confirmation: string }) {
    const response = await apiClient.post<ApiResponse<AuthResponse>>('/auth/register', data);
    return response.data.data;
  },

  async login(email: string, password: string) {
    const response = await apiClient.post<ApiResponse<AuthResponse>>('/auth/login', { email, password });
    const data = response.data.data;
    if (data?.token) {
      localStorage.setItem('auth_token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
    }
    return data;
  },

  async logout() {
    await apiClient.post('/auth/logout');
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
  },

  async getCurrentUser() {
    const response = await apiClient.get<ApiResponse<User>>('/auth/me');
    return response.data.data;
  },

  async updateProfile(data: Partial<User>) {
    const response = await apiClient.patch<ApiResponse<User>>('/auth/profile', data);
    return response.data.data;
  },

  async requestPasswordReset(email: string) {
    const response = await apiClient.post('/auth/password-reset-request', { email });
    return response.data;
  },

  async resetPassword(data: { token: string; password: string; password_confirmation: string }) {
    const response = await apiClient.post('/auth/password-reset', data);
    return response.data;
  },

  async verifyEmail(token: string) {
    const response = await apiClient.post('/auth/verify-email', { token });
    return response.data;
  },

  getStoredUser() {
    if (typeof window !== 'undefined') {
      const user = localStorage.getItem('user');
      return user ? JSON.parse(user) : null;
    }
    return null;
  },

  getToken() {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('auth_token');
    }
    return null;
  },

  isAuthenticated() {
    return !!this.getToken();
  },
};

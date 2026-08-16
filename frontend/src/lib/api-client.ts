import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

export const apiClient: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

const pendingRequests = new Map<string, Promise<any>>();

function getRequestKey(config: AxiosRequestConfig): string {
  return `${config.method?.toUpperCase() || 'GET'}-${config.url}-${JSON.stringify(config.params || {})}-${JSON.stringify(config.data || {})}`;
}

apiClient.interceptors.request.use((config) => {
  const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  const key = getRequestKey(config);
  if (config.method === 'GET' && pendingRequests.has(key)) {
    config.headers['X-Cancel-Pending'] = 'true';
  }

  return config;
});

apiClient.interceptors.response.use(
  (response) => {
    const key = getRequestKey(response.config);
    pendingRequests.delete(key);
    return response;
  },
  (error) => {
    const key = getRequestKey(error.config || {});
    pendingRequests.delete(key);

    if (error.response?.status === 429) {
      const retryAfter = parseInt(error.response.headers['retry-after'] || '1', 10);
      return new Promise((resolve) => {
        setTimeout(() => {
          apiClient.request(error.config).then(resolve).catch(() => resolve(Promise.reject(error)));
        }, retryAfter * 1000);
      });
    }

    if (error.response?.status === 401) {
      if (typeof window !== 'undefined') {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
        window.location.href = '/auth/login';
      }
    }

    return Promise.reject(error);
  }
);

export const requestDeduplication = {
  execute<T>(config: AxiosRequestConfig): Promise<T> {
    const key = getRequestKey(config);

    if (pendingRequests.has(key)) {
      return pendingRequests.get(key) as Promise<T>;
    }

    const promise = apiClient.request(config).finally(() => {
      pendingRequests.delete(key);
    });

    pendingRequests.set(key, promise);

    return promise;
  },
};

export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  data: T[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number;
    to: number;
  };
}

import { apiClient, type ApiResponse, type PaginatedResponse } from '../api-client';
import type { Product, Category } from '../types';

export interface ProductFilters {
  search?: string;
  category_id?: number;
  min_price?: number;
  max_price?: number;
  sort?: 'price' | 'name' | 'newest';
  order?: 'asc' | 'desc';
  page?: number;
  limit?: number;
}

export const productService = {
  async getProducts(filters?: ProductFilters) {
    const params = new URLSearchParams();
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          params.append(key, String(value));
        }
      });
    }
    const response = await apiClient.get<ApiResponse<PaginatedResponse<Product>>>(
      `/products?${params.toString()}`
    );
    return response.data.data;
  },

  async getProduct(id: number) {
    const response = await apiClient.get<ApiResponse<Product>>(`/products/${id}`);
    return response.data.data;
  },

  async getCategories() {
    const response = await apiClient.get<ApiResponse<Category[]>>('/categories');
    return response.data.data || [];
  },

  async searchProducts(query: string) {
    const response = await apiClient.get<ApiResponse<PaginatedResponse<Product>>>('/products', {
      params: { search: query },
    });
    return response.data.data;
  },

  async getFeaturedProducts() {
    const response = await apiClient.get<ApiResponse<Product[]>>('/products/featured');
    return response.data.data || [];
  },
};

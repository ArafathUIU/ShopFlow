import { apiClient, type ApiResponse, type PaginatedResponse } from '../api-client';
import type {
  AdminProduct,
  AdminCategory,
  InventoryItem,
  AdminOrder,
  AdminUser,
  AnalyticsData,
  ProductCreateInput,
  ProductUpdateInput,
  CategoryCreateInput,
  CategoryUpdateInput,
  InventoryAdjustInput,
  OrderStatusUpdateInput,
  UserUpdateInput,
} from '../types';

export const adminService = {
  async getAnalytics() {
    const response = await apiClient.get<ApiResponse<AnalyticsData>>('/admin/analytics/dashboard');
    return response.data.data;
  },

  async getProducts(filters?: { search?: string; category?: string; status?: string; trashed_only?: boolean; page?: number }) {
    const params = new URLSearchParams();
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          params.append(key, String(value));
        }
      });
    }
    const response = await apiClient.get<ApiResponse<PaginatedResponse<AdminProduct>>>(`/admin/products?${params.toString()}`);
    return response.data.data;
  },

  async getProduct(id: number) {
    const response = await apiClient.get<ApiResponse<AdminProduct>>(`/admin/products/${id}`);
    return response.data.data;
  },

  async createProduct(data: ProductCreateInput) {
    const response = await apiClient.post<ApiResponse<AdminProduct>>('/admin/products', data);
    return response.data.data;
  },

  async updateProduct(id: number, data: ProductUpdateInput) {
    const response = await apiClient.put<ApiResponse<AdminProduct>>(`/admin/products/${id}`, data);
    return response.data.data;
  },

  async deleteProduct(id: number) {
    await apiClient.delete(`/admin/products/${id}`);
  },

  async restoreProduct(id: number) {
    const response = await apiClient.post<ApiResponse<AdminProduct>>(`/admin/products/${id}/restore`);
    return response.data.data;
  },

  async archiveProduct(id: number) {
    const response = await apiClient.post<ApiResponse<AdminProduct>>(`/admin/products/${id}/archive`);
    return response.data.data;
  },

  async attachProductImage(id: number, imageUrl: string, altText?: string) {
    const response = await apiClient.post<ApiResponse<AdminProduct>>(`/admin/products/${id}/images`, { url: imageUrl, alt_text: altText });
    return response.data.data;
  },

  async detachProductImage(productId: number, imageId: number) {
    await apiClient.delete(`/admin/products/${productId}/images/${imageId}`);
  },

  async getCategories() {
    const response = await apiClient.get<ApiResponse<AdminCategory[]>>('/admin/categories');
    return response.data.data || [];
  },

  async getCategory(id: number) {
    const response = await apiClient.get<ApiResponse<AdminCategory>>(`/admin/categories/${id}`);
    return response.data.data;
  },

  async createCategory(data: CategoryCreateInput) {
    const response = await apiClient.post<ApiResponse<AdminCategory>>('/admin/categories', data);
    return response.data.data;
  },

  async updateCategory(id: number, data: CategoryUpdateInput) {
    const response = await apiClient.put<ApiResponse<AdminCategory>>(`/admin/categories/${id}`, data);
    return response.data.data;
  },

  async deleteCategory(id: number) {
    await apiClient.delete(`/admin/categories/${id}`);
  },

  async getInventory() {
    const response = await apiClient.get<ApiResponse<PaginatedResponse<InventoryItem>>>('/admin/inventory');
    return response.data.data;
  },

  async adjustInventory(data: InventoryAdjustInput) {
    const response = await apiClient.post<ApiResponse<InventoryItem>>('/admin/inventory/adjust', data);
    return response.data.data;
  },

  async getOrders(filters?: { status?: string; payment_status?: string; from_date?: string; to_date?: string; user_id?: number; search?: string; page?: number }) {
    const params = new URLSearchParams();
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          params.append(key, String(value));
        }
      });
    }
    const response = await apiClient.get<ApiResponse<PaginatedResponse<AdminOrder>>>(`/admin/orders?${params.toString()}`);
    return response.data.data;
  },

  async getOrder(id: number) {
    const response = await apiClient.get<ApiResponse<AdminOrder>>(`/admin/orders/${id}`);
    return response.data.data;
  },

  async updateOrderStatus(id: number, data: OrderStatusUpdateInput) {
    const response = await apiClient.post<ApiResponse<AdminOrder>>(`/admin/orders/${id}/status`, data);
    return response.data.data;
  },

  async cancelOrder(id: number) {
    const response = await apiClient.post<ApiResponse<AdminOrder>>(`/admin/orders/${id}/cancel`);
    return response.data.data;
  },

  async getUsers(filters?: { role?: string; active?: string; verified?: string; search?: string; page?: number }) {
    const params = new URLSearchParams();
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          params.append(key, String(value));
        }
      });
    }
    const response = await apiClient.get<ApiResponse<PaginatedResponse<AdminUser>>>(`/admin/users?${params.toString()}`);
    return response.data.data;
  },

  async getUser(id: number) {
    const response = await apiClient.get<ApiResponse<AdminUser>>(`/admin/users/${id}`);
    return response.data.data;
  },

  async updateUser(id: number, data: UserUpdateInput) {
    const response = await apiClient.put<ApiResponse<AdminUser>>(`/admin/users/${id}`, data);
    return response.data.data;
  },

  async deactivateUser(id: number) {
    const response = await apiClient.post<ApiResponse<AdminUser>>(`/admin/users/${id}/deactivate`);
    return response.data.data;
  },

  async activateUser(id: number) {
    const response = await apiClient.post<ApiResponse<AdminUser>>(`/admin/users/${id}/activate`);
    return response.data.data;
  },
};

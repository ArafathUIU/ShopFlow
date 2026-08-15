import { apiClient, type ApiResponse, type PaginatedResponse } from '../api-client';
import type { Order, Address } from '../types';

export const orderService = {
  async createOrder(data: {
    shipping_address: Address;
    billing_address: Address;
    customer_note?: string;
  }) {
    const response = await apiClient.post<ApiResponse<Order>>('/orders', data);
    return response.data.data;
  },

  async getOrders(page = 1, limit = 10) {
    const response = await apiClient.get<ApiResponse<PaginatedResponse<Order>>>('/orders', {
      params: { page, limit },
    });
    return response.data.data;
  },

  async getOrder(id: number) {
    const response = await apiClient.get<ApiResponse<Order>>(`/orders/${id}`);
    return response.data.data;
  },

  async cancelOrder(id: number) {
    const response = await apiClient.post<ApiResponse<Order>>(`/orders/${id}/cancel`);
    return response.data.data;
  },

  async createPaymentIntent(orderId: number) {
    const response = await apiClient.post<ApiResponse<any>>(`/payments/create-intent`, { order_id: orderId });
    return response.data.data;
  },

  async confirmPayment(paymentIntentId: string) {
    const response = await apiClient.post<ApiResponse<Order>>('/payments/confirm', {
      payment_intent_id: paymentIntentId,
    });
    return response.data.data;
  },
};

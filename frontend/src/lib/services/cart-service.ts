import { apiClient, type ApiResponse } from '../api-client';
import type { Cart, CartItem, Coupon } from '../types';

export const cartService = {
  async getCart() {
    const response = await apiClient.get<ApiResponse<Cart>>('/cart');
    return response.data.data;
  },

  async addToCart(productId: number, quantity: number) {
    const response = await apiClient.post<ApiResponse<Cart>>('/cart/items', { product_id: productId, quantity });
    return response.data.data;
  },

  async updateCartItem(cartItemId: number, quantity: number) {
    const response = await apiClient.patch<ApiResponse<Cart>>(`/cart/items/${cartItemId}`, { quantity });
    return response.data.data;
  },

  async removeFromCart(cartItemId: number) {
    const response = await apiClient.delete<ApiResponse<Cart>>(`/cart/items/${cartItemId}`);
    return response.data.data;
  },

  async clearCart() {
    const response = await apiClient.delete<ApiResponse<Cart>>('/cart');
    return response.data.data;
  },

  async applyCoupon(couponCode: string) {
    const response = await apiClient.post<ApiResponse<Cart>>('/cart/apply-coupon', { coupon_code: couponCode });
    return response.data.data;
  },

  async removeCoupon() {
    const response = await apiClient.delete<ApiResponse<Cart>>('/cart/coupon');
    return response.data.data;
  },

  async validateCoupon(couponCode: string) {
    const response = await apiClient.get<ApiResponse<Coupon>>(`/coupons/${couponCode}/validate`);
    return response.data.data;
  },
};

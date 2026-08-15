import { create } from 'zustand';
import type { Cart, CartItem, Coupon } from '@/lib/types';
import { cartService } from '@/lib/services/cart-service';

function updateCartState(set: (partial: Partial<CartState>) => void, cart: Cart | undefined) {
  if (!cart) {
    set({ isLoading: false });
    return;
  }
  set({
    items: cart.items,
    subtotal: cart.subtotal,
    discount: cart.discount,
    total: cart.total,
    coupon: cart.coupon || null,
    itemCount: cart.items.reduce((sum, item) => sum + item.quantity, 0),
    isLoading: false,
  });
}

interface CartState {
  items: CartItem[];
  subtotal: number;
  discount: number;
  total: number;
  coupon: Coupon | null;
  itemCount: number;
  isLoading: boolean;
  error: string | null;
  fetchCart: () => Promise<void>;
  addItem: (productId: number, quantity: number) => Promise<void>;
  updateItem: (itemId: number, quantity: number) => Promise<void>;
  removeItem: (itemId: number) => Promise<void>;
  clear: () => Promise<void>;
  applyCoupon: (code: string) => Promise<boolean>;
  removeCoupon: () => Promise<void>;
}

export const useCartStore = create<CartState>((set) => ({
  items: [],
  subtotal: 0,
  discount: 0,
  total: 0,
  coupon: null,
  itemCount: 0,
  isLoading: false,
  error: null,

  fetchCart: async () => {
    set({ isLoading: true, error: null });
    try {
      const cart = await cartService.getCart();
      updateCartState(set, cart);
    } catch {
      set({ isLoading: false, error: 'Failed to fetch cart' });
    }
  },
  addItem: async (productId, quantity) => {
    set({ isLoading: true, error: null });
    try {
      const cart = await cartService.addToCart(productId, quantity);
      updateCartState(set, cart);
    } catch {
      set({ isLoading: false, error: 'Failed to add item to cart' });
      throw new Error('Failed to add item to cart');
    }
  },
  updateItem: async (itemId, quantity) => {
    set({ isLoading: true, error: null });
    try {
      const cart = await cartService.updateCartItem(itemId, quantity);
      updateCartState(set, cart);
    } catch {
      set({ isLoading: false, error: 'Failed to update cart item' });
      throw new Error('Failed to update cart item');
    }
  },
  removeItem: async (itemId) => {
    set({ isLoading: true, error: null });
    try {
      const cart = await cartService.removeFromCart(itemId);
      updateCartState(set, cart);
    } catch {
      set({ isLoading: false, error: 'Failed to remove item from cart' });
      throw new Error('Failed to remove item from cart');
    }
  },
  clear: async () => {
    set({ isLoading: true, error: null });
    try {
      const cart = await cartService.clearCart();
      updateCartState(set, cart);
    } catch {
      set({ isLoading: false, error: 'Failed to clear cart' });
      throw new Error('Failed to clear cart');
    }
  },
  applyCoupon: async (code) => {
    set({ isLoading: true, error: null });
    try {
      const cart = await cartService.applyCoupon(code);
      updateCartState(set, cart);
      return true;
    } catch {
      set({ isLoading: false, error: 'Failed to apply coupon' });
      return false;
    }
  },
  removeCoupon: async () => {
    set({ isLoading: true, error: null });
    try {
      const cart = await cartService.removeCoupon();
      updateCartState(set, cart);
    } catch {
      set({ isLoading: false, error: 'Failed to remove coupon' });
      throw new Error('Failed to remove coupon');
    }
  },
}));

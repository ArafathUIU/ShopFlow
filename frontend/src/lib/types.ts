export interface User {
  id: number;
  name: string;
  email: string;
  phone?: string;
  role: 'customer' | 'admin' | 'manager';
  email_verified_at?: string;
  created_at: string;
  updated_at: string;
}

export interface Product {
  id: number;
  name: string;
  slug: string;
  description: string;
  sku: string;
  price: number;
  status: 'active' | 'inactive' | 'archived';
  images: ProductImage[];
  category: Category;
  created_at: string;
  updated_at: string;
}

export interface ProductImage {
  id: number;
  url: string;
  alt_text?: string;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  description?: string;
  status: 'active' | 'inactive';
  created_at: string;
  updated_at: string;
}

export interface CartItem {
  id: number;
  product: Product;
  quantity: number;
  unit_price: number;
  total: number;
}

export interface Cart {
  id: number;
  items: CartItem[];
  subtotal: number;
  discount: number;
  tax: number;
  total: number;
  coupon?: Coupon;
}

export interface Coupon {
  id: number;
  code: string;
  discount_type: 'fixed' | 'percentage';
  discount_value: number;
  maximum_usage: number;
  usage_count: number;
  expires_at: string;
}

export interface Order {
  id: number;
  order_number: string;
  user_id: number;
  status: 'pending' | 'paid' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
  payment_status: 'pending' | 'paid' | 'failed' | 'refunded';
  items: OrderItem[];
  subtotal: number;
  discount: number;
  tax: number;
  shipping_fee: number;
  total: number;
  shipping_address: Address;
  billing_address: Address;
  customer_note?: string;
  placed_at: string;
  created_at: string;
  updated_at: string;
}

export interface OrderItem {
  id: number;
  product: Product;
  quantity: number;
  unit_price: number;
  total: number;
}

export interface Address {
  street: string;
  city: string;
  state: string;
  postal_code: string;
  country: string;
}

export interface WishlistItem {
  id: number;
  product: Product;
  added_at: string;
}

export interface AuthResponse {
  user: User;
  token: string;
}

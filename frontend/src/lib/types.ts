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
  price: {
    cents: number;
    formatted: string;
  };
  compare_at_price?: {
    cents: number;
    formatted: string;
  } | null;
  is_on_sale?: boolean;
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

export interface Money {
  cents: number;
  formatted: string;
}

export interface AdminProduct {
  id: number;
  name: string;
  description: string;
  sku: string;
  price: Money;
  compare_at_price?: Money;
  status: 'active' | 'inactive' | 'archived';
  is_featured: boolean;
  images: ProductImage[];
  category: Category;
  created_at: string;
  updated_at: string;
}

export interface AdminCategory {
  id: number;
  name: string;
  slug: string;
  description?: string;
  status: 'active' | 'inactive';
  products_count: number;
  created_at: string;
  updated_at: string;
}

export interface InventoryItem {
  id: number;
  product_id: number;
  product_name: string;
  sku: string;
  quantity: number;
  reserved: number;
  available: number;
  low_stock_threshold: number;
  status: 'in_stock' | 'low_stock' | 'out_of_stock';
  created_at: string;
  updated_at: string;
}

export interface AdminOrder {
  id: number;
  order_number: string;
  user_id: number;
  customer_name: string;
  customer_email: string;
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

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  phone?: string;
  role: 'customer' | 'admin' | 'manager';
  email_verified_at?: string;
  orders_count: number;
  total_spent: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface AnalyticsData {
  total_revenue: number;
  total_orders: number;
  pending_orders: number;
  total_customers: number;
  revenue_chart: RevenueDataPoint[];
  top_products: TopProduct[];
  recent_orders: AdminOrder[];
  order_status_breakdown: OrderStatusBreakdown[];
  product_stats: ProductStats;
  customer_stats: CustomerStats;
}

export interface RevenueDataPoint {
  date: string;
  revenue: number;
  orders: number;
}

export interface TopProduct {
  product_id: number;
  product_name: string;
  total_sales: number;
  units_sold: number;
}

export interface OrderStatusBreakdown {
  status: string;
  count: number;
}

export interface ProductStats {
  total_products: number;
  active_products: number;
  inactive_products: number;
  archived_products: number;
  low_stock_products: number;
  out_of_stock_products: number;
}

export interface CustomerStats {
  total_customers: number;
  active_customers: number;
  verified_customers: number;
  new_customers_this_month: number;
}

export interface ProductCreateInput {
  name: string;
  description: string;
  sku: string;
  price_cents: number;
  compare_at_price_cents?: number;
  category_id: number;
  status: 'active' | 'inactive' | 'archived';
  is_featured: boolean;
}

export interface ProductUpdateInput {
  name?: string;
  description?: string;
  sku?: string;
  price_cents?: number;
  compare_at_price_cents?: number;
  category_id?: number;
  status?: 'active' | 'inactive' | 'archived';
  is_featured?: boolean;
}

export interface CategoryCreateInput {
  name: string;
  description?: string;
  status: 'active' | 'inactive';
}

export interface CategoryUpdateInput {
  name?: string;
  description?: string;
  status?: 'active' | 'inactive';
}

export interface InventoryAdjustInput {
  product_id: number;
  quantity: number;
  reason: string;
}

export interface OrderStatusUpdateInput {
  status: string;
  reason?: string;
}

export interface UserUpdateInput {
  name?: string;
  email?: string;
  phone?: string;
  role?: 'customer' | 'admin' | 'manager';
}

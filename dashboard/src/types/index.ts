// Domain types — mirror the §4 API contract of the MODIST Laravel backend.

export type Locale = 'en' | 'ar';

export interface User {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  avatar_url: string | null;
}

export interface AuthResponse {
  token: string;
  user: User;
}

/**
 * Product as returned by ProductResource (§4.3).
 * `colors` are #AARRGGBB hex strings, `images` absolute URLs.
 * name/style/description are locale-resolved server-side, but the admin
 * panel also stores bilingual source values for editing.
 */
export interface Product {
  id: string;
  name: string;
  style: string;
  description: string;
  price: number;
  currency: string;
  images: string[];
  colors: string[];
  sizes: string[];
  category_id: string;
  rating: number;
  is_newest: boolean;
  // Optional bilingual source (admin editing convenience; ignored by the app).
  name_en?: string;
  name_ar?: string;
  style_en?: string;
  style_ar?: string;
  description_en?: string;
  description_ar?: string;
}

export interface Category {
  id: string; // slug PK e.g. "tshirt"
  label_key: string; // e.g. "category_tshirt"
  icon_key: string; // e.g. "tshirt"
  sort_order?: number;
}

export type OrderStatus =
  | 'pending_payment'
  | 'paid'
  | 'processing'
  | 'shipped'
  | 'delivered'
  | 'cancelled'
  | 'refunded';

export type PaymentStatus = 'unpaid' | 'paid' | 'failed' | 'refunded';

export interface OrderItem {
  id: string;
  product_id: string;
  name_snapshot: string;
  size: string;
  color_value: number;
  quantity: number;
  unit_price: number;
  line_total: number;
}

export interface Order {
  id: string;
  user_id: string;
  user_name?: string;
  status: OrderStatus;
  payment_status: PaymentStatus;
  subtotal: number;
  discount: number;
  total: number;
  currency: string;
  promo_code: string | null;
  items: OrderItem[];
  created_at: string;
  shipping_address?: string | null;
}

export type PromoType = 'percent' | 'fixed';

export interface PromoCode {
  id: string;
  code: string;
  type: PromoType;
  fraction: number; // 0.10 = 10% (percent) OR absolute amount (fixed)
  active: boolean;
  starts_at: string | null;
  ends_at: string | null;
  usage_limit: number | null;
  used_count: number;
}

export interface CartSummary {
  subtotal: number;
  discount: number;
  total: number;
  currency: string;
  applied_promo: { code: string; fraction: number } | null;
}

export interface CartItem {
  line_id: string; // product_id|size|color
  size: string;
  color: number;
  quantity: number;
  line_total: number;
  product: Product;
}

export interface CartResource {
  items: CartItem[];
  summary: CartSummary;
}

// Standard {data: ...} envelope used by everything except auth.
export interface DataEnvelope<T> {
  data: T;
}

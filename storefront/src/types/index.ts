// Mirrors the public /api/v1 contract — the same surface the Flutter app uses,
// which is why the site and the app share one inventory.

export type Locale = 'en' | 'ar';

export interface User {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  avatar_url: string | null;
  email_verified?: boolean;
}

/** Flat (not data-wrapped) auth response. */
export interface AuthResponse {
  token: string;
  refresh_token?: string;
  user: User;
}

export type StorefrontMode = 'single' | 'multi_department';

export interface AppSettings {
  app_name: string;
  currency: string;
  storefront_mode: StorefrontMode;
  logo_url: string | null;
  shipping_fee: number;
  brand: Partial<Record<'primary' | 'on_primary' | 'accent', string>>;
  flags: {
    card_payment: boolean;
    cash_payment: boolean;
    promo_codes: boolean;
    favorites: boolean;
  };
  // Dashboard-curated Home rails.
  home_rail_categories?: string[];
  max_home_rails?: number;
  home_rail_item_count?: number;
}

export interface Category {
  id: string; // slug — the wire id
  slug?: string;
  parent_id: string | null;
  name: string;
  label_key?: string;
  icon_key?: string | null;
  image_url?: string | null;
  sort_order?: number;
  is_leaf?: boolean;
  product_count?: number;
  children?: Category[];
}

export interface Product {
  id: string;
  name: string;
  style: string;
  description: string;
  price: number;
  currency: string;
  images: string[];
  colors: string[]; // #AARRGGBB
  sizes: string[];
  category_id: string;
  rating: number;
  is_newest: boolean;
}

export type BannerLinkType = 'none' | 'category' | 'product' | 'url';

export interface Banner {
  id: string;
  image_url: string;
  title: string | null;
  subtitle: string | null;
  cta_text: string | null;
  link_type: BannerLinkType;
  link_value: string | null;
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

export interface Cart {
  items: CartItem[];
  summary: CartSummary;
}

export type OrderStatus =
  | 'pending'
  | 'paid'
  | 'shipped'
  | 'delivered'
  | 'cancelled'
  | 'refunded';

export interface OrderItem {
  id: string;
  product_id: string | null;
  name_snapshot: string;
  size: string;
  color_value: number;
  quantity: number;
  unit_price: number;
  line_total: number;
}

export interface Order {
  id: string;
  status: OrderStatus;
  subtotal: number;
  discount: number;
  total: number;
  currency: string;
  promo_code: string | null;
  items: OrderItem[];
  created_at: string;
}

export interface Address {
  id: string;
  address: string;
  city: string | null;
  area: string | null;
  branch: string | null;
  latitude?: number | null;
  longitude?: number | null;
  is_default?: boolean;
}

export interface DataEnvelope<T> {
  data: T;
}

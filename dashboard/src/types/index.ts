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

// ---------------------------------------------------------------------------
// Admin API (§ /api/admin/v1) — Phase 1: Auth, Settings, Categories, Products
// ---------------------------------------------------------------------------

/** Bilingual value used across admin resources. */
export interface LocalizedText {
  en: string;
  ar: string;
}

/** Authenticated admin (POST /auth/login → admin, GET /me → data). */
export interface AdminUser {
  id: string;
  name: string;
  email: string;
  role: string;
  tenant_id: string | null;
}

/** Flat (NOT data-wrapped) login response: { token, admin }. */
export interface AdminAuthResponse {
  token: string;
  admin: AdminUser;
}

export type StorefrontMode = 'single' | 'multi_department';

export interface StoreSettingsFlags {
  card_payment: boolean;
  cash_payment: boolean;
  promo_codes: boolean;
  favorites: boolean;
}

export interface StoreSettingsBrand {
  primary: string; // hex #RRGGBB or #AARRGGBB
  on_primary: string;
  accent: string;
}

/** GET /settings → { data: StoreSettings }. */
export interface StoreSettings {
  app_name: string;
  currency: string;
  storefront_mode: StorefrontMode;
  logo_url: string | null;
  shipping_fee: number;
  brand: StoreSettingsBrand;
  flags: StoreSettingsFlags;
}

/** PATCH /settings accepts any subset. Brand colours are sent FLAT. */
export interface StoreSettingsUpdate {
  app_name?: string;
  currency?: string;
  storefront_mode?: StorefrontMode;
  logo_url?: string | null;
  shipping_fee?: number;
  brand_primary?: string;
  brand_on_primary?: string;
  brand_accent?: string;
  flags?: Partial<StoreSettingsFlags>;
}

/** A node in the categories tree (GET /categories → { data: CategoryNode[] }). */
export interface CategoryNode {
  id: string;
  slug: string;
  parent_id: string | null;
  name: LocalizedText;
  label_key: string;
  icon_key: string | null;
  image_url: string | null;
  sort_order: number;
  is_leaf: boolean;
  product_count: number;
  children: CategoryNode[];
}

/** Body for POST/PATCH /categories. `name` may be bilingual or a plain string. */
export interface CategoryNodeInput {
  name: LocalizedText | string;
  slug?: string;
  parent_id?: string | null;
  icon_key?: string | null;
  image_url?: string | null;
  sort_order?: number;
}

/** Product as returned by the Admin API (GET /products). */
export interface AdminProduct {
  id: string;
  name: LocalizedText;
  style: LocalizedText;
  description: LocalizedText;
  price: number;
  currency: string;
  rating: number;
  is_newest: boolean;
  category_id: string; // leaf category slug
  images: string[];
  sizes: string[];
  colors: string[];
  created_at: string;
}

/** Body for POST/PATCH /products. */
export interface AdminProductInput {
  name: LocalizedText;
  style?: LocalizedText;
  description?: LocalizedText;
  price: number;
  currency?: string;
  is_newest?: boolean;
  rating?: number;
  category_id: string; // leaf slug
  images: string[];
  sizes: string[];
  colors: (string | number)[];
}

/** Laravel paginated envelope. */
export interface Paginated<T> {
  data: T[];
  meta?: {
    current_page?: number;
    last_page?: number;
    per_page?: number;
    total?: number;
  };
  links?: {
    first?: string | null;
    last?: string | null;
    prev?: string | null;
    next?: string | null;
  };
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

// Mirrors the backend Order status machine (Order::STATUS_*).
export type OrderStatus =
  | 'pending'
  | 'paid'
  | 'shipped'
  | 'delivered'
  | 'cancelled'
  | 'refunded';

export type PaymentStatus = 'pending' | 'paid' | 'failed' | 'refunded';

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

// ---------------------------------------------------------------------------
// Banners (GET/POST/PATCH/DELETE /admin/v1/banners) — hero carousel editor.
// ---------------------------------------------------------------------------

export type BannerLinkType = 'none' | 'category' | 'product' | 'url';

export interface AdminBanner {
  id: string;
  image_url: string;
  title: string | null;
  subtitle: string | null;
  cta_text: string | null;
  link_type: BannerLinkType;
  link_value: string | null;
  sort_order: number;
  is_active: boolean;
  starts_at: string | null;
  ends_at: string | null;
}

export interface AdminBannerInput {
  image_url: string;
  title?: string | null;
  subtitle?: string | null;
  cta_text?: string | null;
  link_type: BannerLinkType;
  link_value?: string | null;
  sort_order?: number;
  is_active?: boolean;
  starts_at?: string | null;
  ends_at?: string | null;
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

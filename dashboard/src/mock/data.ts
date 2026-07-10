import type {
  Category,
  Order,
  Product,
  PromoCode,
  User,
} from '@/types';

// Palette + base values mirror the seeders described in the backend plan §5.
const PALETTE = ['#FF1B2A4A', '#FF7B1E1E', '#FF111111', '#FF6B4A2B'];
const SIZES = ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

function img(n: number): string[] {
  return [
    `https://picsum.photos/seed/modist${n}/600/800`,
    `https://picsum.photos/seed/modist${n}b/600/800`,
  ];
}

const PRODUCT_DEFS: Array<{
  id: string;
  name: string;
  name_ar: string;
  style: string;
  category_id: string;
  is_newest: boolean;
}> = [
  { id: 'p1', name: "Men's Casual Navy Shirt", name_ar: 'قميص رجالي كاجوال كحلي', style: 'Men Style', category_id: 'tshirt', is_newest: true },
  { id: 'p2', name: 'Classic White Tee', name_ar: 'تيشيرت أبيض كلاسيكي', style: 'Unisex', category_id: 'tshirt', is_newest: false },
  { id: 'p3', name: 'Slim Fit Chino Pants', name_ar: 'بنطلون تشينو ضيق', style: 'Men Style', category_id: 'pants', is_newest: true },
  { id: 'p4', name: 'Denim Jacket', name_ar: 'جاكيت جينز', style: 'Street', category_id: 'jacket', is_newest: false },
  { id: 'p5', name: 'Summer Cargo Shorts', name_ar: 'شورت كارجو صيفي', style: 'Casual', category_id: 'shorts', is_newest: true },
  { id: 'p6', name: 'Leather Sneakers', name_ar: 'حذاء رياضي جلد', style: 'Sport', category_id: 'shoes', is_newest: false },
  { id: 'p7', name: 'Bomber Jacket', name_ar: 'جاكيت بومبر', style: 'Street', category_id: 'jacket', is_newest: true },
  { id: 'p8', name: 'Running Shoes', name_ar: 'حذاء جري', style: 'Sport', category_id: 'shoes', is_newest: true },
];

export const seedCategories: Category[] = [
  { id: 'tshirt', label_key: 'category_tshirt', icon_key: 'tshirt', sort_order: 1 },
  { id: 'pants', label_key: 'category_pants', icon_key: 'pants', sort_order: 2 },
  { id: 'jacket', label_key: 'category_jacket', icon_key: 'jacket', sort_order: 3 },
  { id: 'shorts', label_key: 'category_shorts', icon_key: 'shorts', sort_order: 4 },
  { id: 'shoes', label_key: 'category_shoes', icon_key: 'shoes', sort_order: 5 },
];

export const seedProducts: Product[] = PRODUCT_DEFS.map((d, i) => ({
  id: d.id,
  name: d.name,
  name_en: d.name,
  name_ar: d.name_ar,
  style: d.style,
  style_en: d.style,
  style_ar: d.style,
  description: `${d.name} — premium quality from the MODIST collection.`,
  description_en: `${d.name} — premium quality from the MODIST collection.`,
  description_ar: `${d.name_ar} — جودة ممتازة من مجموعة مودِست.`,
  price: 820,
  currency: 'EGP',
  images: img(i + 1),
  colors: [...PALETTE],
  sizes: [...SIZES],
  category_id: d.category_id,
  rating: 4.6,
  is_newest: d.is_newest,
}));

export const seedPromos: PromoCode[] = [
  { id: 'pr1', code: 'MODIST10', type: 'percent', fraction: 0.1, active: true, starts_at: null, ends_at: null, usage_limit: 1000, used_count: 124 },
  { id: 'pr2', code: 'WELCOME15', type: 'percent', fraction: 0.15, active: true, starts_at: null, ends_at: null, usage_limit: 500, used_count: 87 },
  { id: 'pr3', code: 'XX032910', type: 'percent', fraction: 0.2, active: false, starts_at: null, ends_at: '2026-01-01', usage_limit: 100, used_count: 100 },
];

export const seedUsers: User[] = [
  { id: '1', name: 'Admin MODIST', email: 'admin@modist.test', phone: '+201000000000', avatar_url: null },
  { id: '7', name: 'Sara Ahmed', email: 'sara@example.com', phone: '+201111111111', avatar_url: null },
  { id: '8', name: 'Omar Khaled', email: 'omar@example.com', phone: '+201222222222', avatar_url: null },
  { id: '9', name: 'Layla Hassan', email: 'layla@example.com', phone: '+201333333333', avatar_url: null },
  { id: '10', name: 'Youssef Ali', email: 'youssef@example.com', phone: '+201444444444', avatar_url: null },
];

function makeOrder(
  id: string,
  userId: string,
  userName: string,
  status: Order['status'],
  paymentStatus: Order['payment_status'],
  daysAgo: number,
  promo: string | null,
): Order {
  const p = seedProducts[Number(id.slice(1)) % seedProducts.length];
  const qty = (Number(id.slice(1)) % 3) + 1;
  const subtotal = p.price * qty;
  const discount = promo ? Math.round(subtotal * 0.1) : 0;
  const created = new Date();
  created.setDate(created.getDate() - daysAgo);
  return {
    id,
    user_id: userId,
    user_name: userName,
    status,
    payment_status: paymentStatus,
    subtotal,
    discount,
    total: subtotal - discount,
    currency: 'EGP',
    promo_code: promo,
    created_at: created.toISOString(),
    shipping_address: 'Cairo, Egypt',
    items: [
      {
        id: `${id}-1`,
        product_id: p.id,
        name_snapshot: p.name,
        size: 'M',
        color_value: 4279371338,
        quantity: qty,
        unit_price: p.price,
        line_total: subtotal,
      },
    ],
  };
}

export const seedOrders: Order[] = [
  makeOrder('o1', '7', 'Sara Ahmed', 'paid', 'paid', 1, 'MODIST10'),
  makeOrder('o2', '8', 'Omar Khaled', 'pending', 'pending', 2, null),
  makeOrder('o3', '9', 'Layla Hassan', 'shipped', 'paid', 4, 'WELCOME15'),
  makeOrder('o4', '10', 'Youssef Ali', 'delivered', 'paid', 7, null),
  makeOrder('o5', '7', 'Sara Ahmed', 'cancelled', 'refunded', 9, null),
  makeOrder('o6', '8', 'Omar Khaled', 'paid', 'paid', 11, 'MODIST10'),
  makeOrder('o7', '9', 'Layla Hassan', 'paid', 'paid', 14, null),
];

export const MOCK_ADMIN = {
  email: 'admin@modist.test',
  password: 'password',
};

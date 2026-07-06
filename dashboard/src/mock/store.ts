import type {
  Category,
  Order,
  Product,
  PromoCode,
  User,
} from '@/types';
import {
  seedCategories,
  seedOrders,
  seedProducts,
  seedPromos,
  seedUsers,
} from './data';

// Mutable in-memory store so mutations feel live in mock mode.
interface MockState {
  products: Product[];
  categories: Category[];
  promos: PromoCode[];
  orders: Order[];
  users: User[];
}

function clone<T>(v: T): T {
  return JSON.parse(JSON.stringify(v)) as T;
}

export const mockState: MockState = {
  products: clone(seedProducts),
  categories: clone(seedCategories),
  promos: clone(seedPromos),
  orders: clone(seedOrders),
  users: clone(seedUsers),
};

export function nextId(prefix: string, list: { id: string }[]): string {
  let max = 0;
  for (const item of list) {
    const n = Number(item.id.replace(/\D/g, ''));
    if (!Number.isNaN(n) && n > max) max = n;
  }
  return `${prefix}${max + 1}`;
}

// Simulate network latency for realistic loading states.
export function delay<T>(value: T, ms = 350): Promise<T> {
  return new Promise((resolve) => setTimeout(() => resolve(value), ms));
}

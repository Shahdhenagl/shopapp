import type { Cart } from '@/types';

/**
 * The server's cart summary stops at subtotal − discount; shipping is applied
 * by the client (the app's CartState does the same), so a screen that shows
 * `summary.total` as the final figure quietly undercharges by the shipping fee.
 */
export interface Totals {
  subtotal: number;
  discount: number;
  shipping: number;
  total: number;
  currency: string;
}

export function totalsFor(cart: Cart, shippingFee: number): Totals {
  const { subtotal, discount, currency } = cart.summary;

  // Nothing in the basket, nothing to ship.
  const shipping = cart.items.length > 0 ? shippingFee : 0;

  return {
    subtotal,
    discount,
    shipping,
    total: subtotal - discount + shipping,
    currency,
  };
}

import type { OrderStatus, PaymentStatus } from '@/types';

type Tone = 'gray' | 'green' | 'red' | 'yellow' | 'blue' | 'purple' | 'orange';

export const ORDER_STATUSES: OrderStatus[] = [
  'pending_payment',
  'paid',
  'processing',
  'shipped',
  'delivered',
  'cancelled',
  'refunded',
];

export function orderStatusTone(status: OrderStatus): Tone {
  switch (status) {
    case 'pending_payment':
      return 'yellow';
    case 'paid':
      return 'blue';
    case 'processing':
      return 'purple';
    case 'shipped':
      return 'orange';
    case 'delivered':
      return 'green';
    case 'cancelled':
      return 'red';
    case 'refunded':
      return 'gray';
  }
}

export function paymentStatusTone(status: PaymentStatus): Tone {
  switch (status) {
    case 'paid':
      return 'green';
    case 'unpaid':
      return 'yellow';
    case 'failed':
      return 'red';
    case 'refunded':
      return 'gray';
  }
}

export function humanize(value: string): string {
  return value
    .split('_')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}

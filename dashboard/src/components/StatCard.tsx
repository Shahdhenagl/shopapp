import type { LucideIcon } from 'lucide-react';
import { clsx } from 'clsx';

interface StatCardProps {
  label: string;
  value: string | number;
  icon: LucideIcon;
  tone?: 'brand' | 'green' | 'orange' | 'purple';
}

const tones = {
  brand: 'bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300',
  green: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
  orange:
    'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
  purple:
    'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
};

export function StatCard({ label, value, icon: Icon, tone = 'brand' }: StatCardProps) {
  return (
    <div className="card flex items-center gap-4 p-5">
      <div className={clsx('rounded-xl p-3', tones[tone])}>
        <Icon size={24} />
      </div>
      <div>
        <p className="text-sm text-slate-500 dark:text-slate-400">{label}</p>
        <p className="text-2xl font-bold">{value}</p>
      </div>
    </div>
  );
}

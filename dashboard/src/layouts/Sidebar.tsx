import { NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  Package,
  Tags,
  ShoppingCart,
  Ticket,
  Image,
  Users,
  Settings,
  X,
} from 'lucide-react';
import { clsx } from 'clsx';
import { useLocaleStore } from '@/store/locale';

const items = [
  { to: '/', key: 'nav_dashboard', icon: LayoutDashboard, end: true },
  { to: '/products', key: 'nav_products', icon: Package },
  { to: '/categories', key: 'nav_categories', icon: Tags },
  { to: '/orders', key: 'nav_orders', icon: ShoppingCart },
  { to: '/promos', key: 'nav_promos', icon: Ticket },
  { to: '/banners', key: 'nav_banners', icon: Image },
  { to: '/users', key: 'nav_users', icon: Users },
  { to: '/settings', key: 'nav_settings', icon: Settings },
];

export function Sidebar({
  open,
  onClose,
}: {
  open: boolean;
  onClose: () => void;
}) {
  const t = useLocaleStore((s) => s.t);

  return (
    <>
      {open && (
        <div
          className="fixed inset-0 z-30 bg-black/40 lg:hidden"
          onClick={onClose}
        />
      )}
      <aside
        className={clsx(
          'fixed inset-y-0 z-40 flex w-64 flex-col border-e border-slate-200 bg-white transition-transform dark:border-slate-800 dark:bg-slate-900 lg:static lg:translate-x-0',
          'start-0',
          open ? 'translate-x-0' : '-translate-x-full rtl:translate-x-full',
        )}
      >
        <div className="flex items-center justify-between px-6 py-5">
          <div className="flex items-center gap-2">
            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-700 font-bold text-white">
              M
            </div>
            <span className="text-lg font-bold tracking-tight">MODIST</span>
          </div>
          <button
            onClick={onClose}
            className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800"
            aria-label="Close menu"
          >
            <X size={20} />
          </button>
        </div>
        <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-2">
          {items.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              onClick={onClose}
              className={({ isActive }) =>
                clsx(
                  'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                  isActive
                    ? 'bg-brand-700 text-white'
                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
                )
              }
            >
              <item.icon size={18} />
              {t(item.key)}
            </NavLink>
          ))}
        </nav>
        <div className="border-t border-slate-200 px-6 py-4 text-xs text-slate-400 dark:border-slate-800">
          MODIST Admin v1.0
        </div>
      </aside>
    </>
  );
}

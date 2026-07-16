import { useState } from 'react';
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Home, LayoutGrid, Search, ShoppingCart, User } from 'lucide-react';
import { cartApi, catalog } from '@/api';
import { useAuth } from '@/store/auth';

function useCartCount() {
  const authed = useAuth((s) => Boolean(s.token));
  const { data } = useQuery({
    queryKey: ['cart'],
    queryFn: () => cartApi.get(),
    enabled: authed,
  });
  return data?.items.reduce((n, i) => n + i.quantity, 0) ?? 0;
}

export function Layout() {
  const navigate = useNavigate();
  const [term, setTerm] = useState('');
  const count = useCartCount();

  const { data: settings } = useQuery({
    queryKey: ['settings'],
    queryFn: () => catalog.settings(),
  });

  const submitSearch = (e: React.FormEvent) => {
    e.preventDefault();
    navigate(`/shop?q=${encodeURIComponent(term.trim())}`);
  };

  return (
    <div className="flex min-h-full flex-col bg-canvas">
      {/* Header — flat surface, hairline underneath (no shadow). */}
      <header className="sticky top-0 z-30 border-b border-hairline bg-surface">
        <div className="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
          <Link to="/" className="flex items-center gap-2">
            {settings?.logo_url ? (
              <img
                src={settings.logo_url}
                alt={settings.app_name}
                className="h-8 w-8 rounded-lg object-cover"
              />
            ) : (
              <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-on-primary">
                M
              </span>
            )}
            <span className="text-title font-bold text-ink">
              {settings?.app_name ?? 'MODIST'}
            </span>
          </Link>

          <form onSubmit={submitSearch} className="relative mx-auto hidden flex-1 sm:block">
            <Search
              size={16}
              className="pointer-events-none absolute start-4 top-1/2 -translate-y-1/2 text-hint"
            />
            <input
              className="field ps-10"
              placeholder="ابحث عن منتج…"
              value={term}
              onChange={(e) => setTerm(e.target.value)}
            />
          </form>

          <nav className="flex items-center gap-1">
            <Link
              to="/cart"
              className="relative rounded-pill p-2 text-ink hover:bg-surface-variant"
              aria-label="السلة"
            >
              <ShoppingCart size={20} />
              {count > 0 && (
                <span className="absolute -end-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-pill bg-primary px-1 text-[10px] font-bold text-on-primary">
                  {count}
                </span>
              )}
            </Link>
            <Link
              to="/account"
              className="rounded-pill p-2 text-ink hover:bg-surface-variant"
              aria-label="حسابي"
            >
              <User size={20} />
            </Link>
          </nav>
        </div>
      </header>

      <main className="mx-auto w-full max-w-6xl flex-1 px-4 pb-24 pt-5 sm:pb-10">
        <Outlet />
      </main>

      {/* Bottom nav — the one lifted surface, mirroring the app. */}
      <nav className="fixed inset-x-0 bottom-0 z-30 border-t border-hairline bg-surface shadow-nav sm:hidden">
        <div className="flex items-center justify-around py-2">
          {[
            { to: '/', icon: Home, label: 'الرئيسية', end: true },
            { to: '/shop', icon: LayoutGrid, label: 'المتجر' },
            { to: '/cart', icon: ShoppingCart, label: 'السلة' },
            { to: '/account', icon: User, label: 'حسابي' },
          ].map(({ to, icon: Icon, label, end }) => (
            <NavLink
              key={to}
              to={to}
              end={end}
              className={({ isActive }) =>
                `flex flex-col items-center gap-1 px-3 py-1 text-nav ${
                  isActive ? 'text-primary' : 'text-muted'
                }`
              }
            >
              <Icon size={20} />
              {label}
            </NavLink>
          ))}
        </div>
      </nav>
    </div>
  );
}

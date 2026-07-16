import { useState } from 'react';
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Heart, Home, LayoutGrid, Search, ShoppingCart, User } from 'lucide-react';
import { cartApi, catalog } from '@/api';
import { LocaleToggle } from '@/components/LocaleToggle';
import { ThemeToggle } from '@/components/ThemeToggle';
import { useAuth } from '@/store/auth';
import { useCatalogKey } from '@/hooks/useCatalogKey';
import { useLocale } from '@/store/locale';

function useCartCount() {
  const authed = useAuth((s) => Boolean(s.token));
  const { data } = useQuery({
    queryKey: ['cart'],
    queryFn: () => cartApi.get(),
    enabled: authed,
  });
  return data?.items.reduce((n, i) => n + i.quantity, 0) ?? 0;
}

function CartBadge({ count }: { count: number }) {
  if (count === 0) return null;
  return (
    <span className="absolute -end-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-pill bg-primary px-1 text-[10px] font-bold text-on-primary">
      {count > 99 ? '99+' : count}
    </span>
  );
}

export function Layout() {
  const navigate = useNavigate();
  const t = useLocale((s) => s.t);
  const [term, setTerm] = useState('');
  const count = useCartCount();

  const { data: settings } = useQuery({
    queryKey: useCatalogKey('settings'),
    queryFn: () => catalog.settings(),
  });

  const submitSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const q = term.trim();
    navigate(q ? `/shop?q=${encodeURIComponent(q)}` : '/shop');
  };

  return (
    <div className="flex min-h-full flex-col bg-canvas">
      {/* Header — flat surface, hairline underneath (the app has no shadows). */}
      <header className="sticky top-0 z-30 border-b border-hairline bg-surface">
        <div className="mx-auto flex max-w-[1600px] items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
          <Link to="/" className="flex flex-none items-center gap-2">
            {settings?.logo_url ? (
              <img
                src={settings.logo_url}
                alt={settings.app_name}
                className="h-9 w-9 rounded-input object-cover"
              />
            ) : (
              <span className="grid h-9 w-9 place-items-center rounded-input bg-primary font-bold text-on-primary">
                {settings?.app_name?.[0] ?? 'M'}
              </span>
            )}
            <span className="hidden text-title font-bold text-ink sm:block">
              {settings?.app_name ?? 'MODIST'}
            </span>
          </Link>

          <form onSubmit={submitSearch} className="relative flex-1">
            <Search
              size={16}
              className="pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-hint"
            />
            <input
              className="field py-2.5 ps-10"
              placeholder={t('search_placeholder')}
              value={term}
              onChange={(e) => setTerm(e.target.value)}
            />
          </form>

          <nav className="flex flex-none items-center gap-1">
            {/* Language stays reachable on mobile too — it's not a preference
                worth burying, especially on a bilingual storefront. */}
            <LocaleToggle />
            <div className="hidden sm:block">
              <ThemeToggle />
            </div>
            <Link
              to="/favorites"
              className="hidden rounded-pill p-2 text-ink hover:bg-surface-variant sm:block"
              aria-label={t('nav_favorites')}
            >
              <Heart size={20} />
            </Link>
            <Link
              to="/cart"
              className="relative rounded-pill p-2 text-ink hover:bg-surface-variant"
              aria-label={t('nav_cart')}
            >
              <ShoppingCart size={20} />
              <CartBadge count={count} />
            </Link>
            <Link
              to="/account"
              className="hidden rounded-pill p-2 text-ink hover:bg-surface-variant sm:block"
              aria-label={t('nav_profile')}
            >
              <User size={20} />
            </Link>
          </nav>
        </div>
      </header>

      <main className="mx-auto w-full max-w-[1600px] flex-1 px-4 pb-24 pt-5 sm:px-6 sm:pb-12 lg:px-8">
        <Outlet />
      </main>

      <footer className="hidden border-t border-hairline bg-surface py-6 sm:block">
        <div className="mx-auto flex max-w-[1600px] items-center justify-between px-4 text-caption sm:px-6 lg:px-8 text-muted">
          <span>
            © {new Date().getFullYear()} {settings?.app_name ?? 'MODIST'}
          </span>
          <div className="flex items-center gap-2">
            <LocaleToggle />
            <ThemeToggle />
          </div>
        </div>
      </footer>

      {/* Bottom nav — the app's five tabs, Cart raised at the centre. It's the
          one lifted surface; everything else is flat. */}
      <nav className="fixed inset-x-0 bottom-0 z-30 border-t border-hairline bg-surface shadow-nav sm:hidden">
        <div className="flex items-end justify-around py-2">
          {[
            { to: '/', icon: Home, label: t('nav_home'), end: true },
            { to: '/favorites', icon: Heart, label: t('nav_favorites') },
            { to: '/cart', icon: ShoppingCart, label: t('nav_cart'), raised: true },
            { to: '/shop', icon: LayoutGrid, label: t('nav_categories') },
            { to: '/account', icon: User, label: t('nav_profile') },
          ].map(({ to, icon: Icon, label, end, raised }) =>
            raised ? (
              <NavLink
                key={to}
                to={to}
                className="flex flex-col items-center gap-1 text-nav text-muted"
              >
                <span className="relative -mt-6 grid h-12 w-12 place-items-center rounded-pill bg-primary text-on-primary shadow-nav">
                  <Icon size={20} />
                  <CartBadge count={count} />
                </span>
                {label}
              </NavLink>
            ) : (
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
            ),
          )}
        </div>
      </nav>
    </div>
  );
}

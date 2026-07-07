import { Globe, LogOut, Menu, Moon, Sun } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useLocaleStore } from '@/store/locale';
import { useAuthStore } from '@/store/auth';
import { adminAuthService } from '@/api';
import { useTheme } from '@/hooks/useTheme';
import { Button } from '@/components/Button';

export function Topbar({ onMenu }: { onMenu: () => void }) {
  const { locale, toggleLocale, t } = useLocaleStore();
  const admin = useAuthStore((s) => s.admin);
  const clearSession = useAuthStore((s) => s.clearSession);
  const navigate = useNavigate();
  const { theme, toggle } = useTheme();

  const handleLogout = async () => {
    try {
      await adminAuthService.logout();
    } catch {
      // ignore network error on logout
    }
    clearSession();
    navigate('/login', { replace: true });
  };

  return (
    <header className="sticky top-0 z-20 flex items-center justify-between border-b border-slate-200 bg-white/80 px-4 py-3 backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
      <div className="flex items-center gap-3">
        <button
          onClick={onMenu}
          className="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800"
          aria-label="Menu"
        >
          <Menu size={20} />
        </button>
        <span className="hidden text-sm text-slate-500 sm:block dark:text-slate-400">
          {admin ? `${admin.name}` : ''}
        </span>
      </div>

      <div className="flex items-center gap-2">
        <button
          onClick={toggle}
          className="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
          aria-label="Toggle theme"
        >
          {theme === 'dark' ? <Sun size={18} /> : <Moon size={18} />}
        </button>
        <button
          onClick={toggleLocale}
          className="flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
          aria-label="Toggle language"
        >
          <Globe size={18} />
          {locale === 'en' ? 'EN' : 'AR'}
        </button>
        <Button
          variant="ghost"
          size="sm"
          icon={<LogOut size={16} />}
          onClick={handleLogout}
        >
          <span className="hidden sm:inline">{t('logout')}</span>
        </Button>
      </div>
    </header>
  );
}

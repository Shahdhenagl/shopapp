import { Monitor, Moon, Sun } from 'lucide-react';
import { useTheme, type ThemeMode } from '@/store/theme';

const OPTIONS: { mode: ThemeMode; icon: typeof Sun; label: string }[] = [
  { mode: 'light', icon: Sun, label: 'فاتح' },
  { mode: 'dark', icon: Moon, label: 'داكن' },
  { mode: 'system', icon: Monitor, label: 'حسب النظام' },
];

export function ThemeToggle() {
  const { mode, setMode } = useTheme();

  return (
    <div
      className="flex items-center gap-0.5 rounded-pill border border-hairline p-0.5"
      role="group"
      aria-label="المظهر"
    >
      {OPTIONS.map(({ mode: value, icon: Icon, label }) => (
        <button
          key={value}
          type="button"
          onClick={() => setMode(value)}
          title={label}
          aria-label={label}
          aria-pressed={mode === value}
          className={`rounded-pill p-1.5 transition ${
            mode === value
              ? 'bg-primary text-on-primary'
              : 'text-muted hover:text-ink'
          }`}
        >
          <Icon size={15} />
        </button>
      ))}
    </div>
  );
}

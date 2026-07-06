import { Link } from 'react-router-dom';
import { Button } from '@/components/Button';

export function NotFound() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-4">
      <h1 className="text-6xl font-bold text-brand-700">404</h1>
      <p className="text-slate-500">Page not found</p>
      <Link to="/">
        <Button>Back to Dashboard</Button>
      </Link>
    </div>
  );
}

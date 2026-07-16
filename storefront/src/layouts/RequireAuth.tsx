import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '@/store/auth';

export function RequireAuth() {
  const token = useAuth((s) => s.token);
  const location = useLocation();

  if (!token) {
    // Come back here once they've signed in.
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  return <Outlet />;
}

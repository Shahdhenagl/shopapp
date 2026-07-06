import { useEffect } from 'react';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { useAuthStore } from '@/store/auth';
import { setUnauthorizedHandler } from '@/api';
import { ToastContainer } from '@/components/ToastContainer';
import { ProtectedRoute } from '@/layouts/ProtectedRoute';
import { AppLayout } from '@/layouts/AppLayout';
import { Login } from '@/pages/Login';
import { Dashboard } from '@/pages/Dashboard';
import { Products } from '@/pages/products/Products';
import { Categories } from '@/pages/Categories';
import { Orders } from '@/pages/Orders';
import { Promos } from '@/pages/Promos';
import { Users } from '@/pages/Users';
import { Settings } from '@/pages/Settings';
import { NotFound } from '@/pages/NotFound';

export default function App() {
  const clearSession = useAuthStore((s) => s.clearSession);

  useEffect(() => {
    // Wire the Axios 401 handler to the auth store (clears session).
    setUnauthorizedHandler(() => clearSession());
  }, [clearSession]);

  return (
    <BrowserRouter basename={import.meta.env.BASE_URL}>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route element={<ProtectedRoute />}>
          <Route element={<AppLayout />}>
            <Route path="/" element={<Dashboard />} />
            <Route path="/products" element={<Products />} />
            <Route path="/categories" element={<Categories />} />
            <Route path="/orders" element={<Orders />} />
            <Route path="/promos" element={<Promos />} />
            <Route path="/users" element={<Users />} />
            <Route path="/settings" element={<Settings />} />
          </Route>
        </Route>
        <Route path="/404" element={<NotFound />} />
        <Route path="*" element={<Navigate to="/404" replace />} />
      </Routes>
      <ToastContainer />
    </BrowserRouter>
  );
}

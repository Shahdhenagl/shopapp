import { useEffect } from 'react';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { setUnauthorizedHandler } from '@/api';
import { useAuth } from '@/store/auth';
import { Layout } from '@/layouts/Layout';
import { RequireAuth } from '@/layouts/RequireAuth';
import { Home } from '@/pages/Home';
import { Catalog } from '@/pages/Catalog';
import { ProductDetail } from '@/pages/ProductDetail';
import { CartPage } from '@/pages/CartPage';
import { Favorites } from '@/pages/Favorites';
import { Checkout } from '@/pages/Checkout';
import { Login } from '@/pages/Login';
import { Register } from '@/pages/Register';
import { Account } from '@/pages/Account';

export default function App() {
  const clear = useAuth((s) => s.clear);

  useEffect(() => {
    setUnauthorizedHandler(() => clear());
  }, [clear]);

  return (
    <BrowserRouter basename={import.meta.env.BASE_URL}>
      <Routes>
        <Route element={<Layout />}>
          <Route path="/" element={<Home />} />
          <Route path="/shop" element={<Catalog />} />
          <Route path="/c/:categoryId" element={<Catalog />} />
          <Route path="/p/:productId" element={<ProductDetail />} />
          <Route path="/cart" element={<CartPage />} />
          <Route path="/favorites" element={<Favorites />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />

          <Route element={<RequireAuth />}>
            <Route path="/checkout" element={<Checkout />} />
            <Route path="/account" element={<Account />} />
          </Route>
        </Route>
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

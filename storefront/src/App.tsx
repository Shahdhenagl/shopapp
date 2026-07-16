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
import { EditProfile } from '@/pages/EditProfile';
import { Orders } from '@/pages/Orders';
import { Addresses } from '@/pages/Addresses';
import { Notifications } from '@/pages/Notifications';
import { Settings } from '@/pages/Settings';

export default function App() {
  const clear = useAuth((s) => s.clear);

  useEffect(() => {
    setUnauthorizedHandler(() => clear());
  }, [clear]);

  // NOTE: the locale is part of every catalog query key (see useCatalogKey), so
  // switching language refetches naturally. Do NOT reach for queryClient.clear()
  // here — clearing the cache from an effect races the in-flight first fetches
  // and leaves them pending forever (the whole page sits on its skeletons).

  return (
    <BrowserRouter basename={import.meta.env.BASE_URL}>
      <Routes>
        <Route element={<Layout />}>
          {/* Guest-by-default: the whole storefront browses without an account. */}
          <Route path="/" element={<Home />} />
          <Route path="/shop" element={<Catalog />} />
          <Route path="/c/:categoryId" element={<Catalog />} />
          <Route path="/p/:productId" element={<ProductDetail />} />
          <Route path="/cart" element={<CartPage />} />
          <Route path="/favorites" element={<Favorites />} />
          <Route path="/settings" element={<Settings />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />

          {/* Auth is demanded at the action, not the door. */}
          <Route element={<RequireAuth />}>
            <Route path="/checkout" element={<Checkout />} />
            <Route path="/account" element={<Account />} />
            <Route path="/account/edit" element={<EditProfile />} />
            <Route path="/orders" element={<Orders />} />
            <Route path="/addresses" element={<Addresses />} />
            <Route path="/notifications" element={<Notifications />} />
          </Route>
        </Route>
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

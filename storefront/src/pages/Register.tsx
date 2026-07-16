import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { auth, getErrorMessage } from '@/api';
import { useAuth } from '@/store/auth';

export function Register() {
  const navigate = useNavigate();
  const setSession = useAuth((s) => s.setSession);

  const [form, setForm] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
  });
  const [error, setError] = useState<string | null>(null);

  const set = (key: keyof typeof form, value: string) =>
    setForm((prev) => ({ ...prev, [key]: value }));

  // Sign-up is instant: the API returns a token straight away and the account
  // starts unverified — verification is a soft nudge, not a gate.
  const mutation = useMutation({
    mutationFn: () =>
      auth.register({
        name: form.name.trim(),
        email: form.email.trim(),
        phone: form.phone.trim() || undefined,
        password: form.password,
      }),
    onSuccess: (res) => {
      setSession(res.token, res.user, res.refresh_token);
      navigate('/', { replace: true });
    },
    onError: (e) => setError(getErrorMessage(e)),
  });

  return (
    <div className="mx-auto max-w-sm py-8">
      <h1 className="mb-1 text-title font-bold text-ink">إنشاء حساب</h1>
      <p className="mb-5 text-body text-muted">دقيقة واحدة وتبدأ التسوّق.</p>

      <form
        className="space-y-3"
        onSubmit={(e) => {
          e.preventDefault();
          setError(null);
          mutation.mutate();
        }}
      >
        <div>
          <label className="label">الاسم</label>
          <input
            className="field"
            value={form.name}
            onChange={(e) => set('name', e.target.value)}
            required
          />
        </div>
        <div>
          <label className="label">البريد الإلكتروني</label>
          <input
            type="email"
            className="field"
            value={form.email}
            onChange={(e) => set('email', e.target.value)}
            required
          />
        </div>
        <div>
          <label className="label">رقم الهاتف</label>
          <input
            className="field"
            dir="ltr"
            placeholder="+20…"
            value={form.phone}
            onChange={(e) => set('phone', e.target.value)}
          />
        </div>
        <div>
          <label className="label">كلمة المرور</label>
          <input
            type="password"
            className="field"
            minLength={8}
            value={form.password}
            onChange={(e) => set('password', e.target.value)}
            required
          />
        </div>

        {error && <p className="field-error">{error}</p>}

        <button className="btn w-full" disabled={mutation.isPending}>
          {mutation.isPending ? '…' : 'إنشاء الحساب'}
        </button>
      </form>

      <p className="mt-4 text-center text-body text-muted">
        لديك حساب بالفعل؟{' '}
        <Link to="/login" className="font-semibold text-accent">
          سجّل الدخول
        </Link>
      </p>
    </div>
  );
}

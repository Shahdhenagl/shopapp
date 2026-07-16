import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { auth, getErrorMessage } from '@/api';
import { useAuth } from '@/store/auth';
import { useLocale } from '@/store/locale';

export function Register() {
  const t = useLocale((s) => s.t);
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
      <h1 className="mb-1 text-title font-bold text-ink">{t('sign_up')}</h1>
      <p className="mb-5 text-body text-muted">{t('sign_up_blurb')}</p>

      <form
        className="space-y-3"
        onSubmit={(e) => {
          e.preventDefault();
          setError(null);
          mutation.mutate();
        }}
      >
        <div>
          <label className="label">{t('name')}</label>
          <input
            className="field"
            value={form.name}
            onChange={(e) => set('name', e.target.value)}
            required
          />
        </div>
        <div>
          <label className="label">{t('email')}</label>
          <input
            type="email"
            className="field"
            value={form.email}
            onChange={(e) => set('email', e.target.value)}
            required
          />
        </div>
        <div>
          <label className="label">{t('phone')}</label>
          <input
            className="field"
            dir="ltr"
            placeholder="+20…"
            value={form.phone}
            onChange={(e) => set('phone', e.target.value)}
          />
        </div>
        <div>
          <label className="label">{t('password')}</label>
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
          {mutation.isPending ? '…' : t('sign_up')}
        </button>
      </form>

      <p className="mt-4 text-center text-body text-muted">
        {t('have_account')}{' '}
        <Link to="/login" className="font-semibold text-accent">
          {t('sign_in')}
        </Link>
      </p>
    </div>
  );
}

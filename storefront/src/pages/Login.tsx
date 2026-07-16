import { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { auth, getErrorMessage } from '@/api';
import { useAuth } from '@/store/auth';

export function Login() {
  const navigate = useNavigate();
  const location = useLocation();
  const setSession = useAuth((s) => s.setSession);

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);

  const from = (location.state as { from?: string } | null)?.from ?? '/';

  const mutation = useMutation({
    mutationFn: () => auth.login(email.trim(), password),
    onSuccess: (res) => {
      setSession(res.token, res.user, res.refresh_token);
      navigate(from, { replace: true });
    },
    onError: (e) => setError(getErrorMessage(e)),
  });

  return (
    <div className="mx-auto max-w-sm py-8">
      <h1 className="mb-1 text-title font-bold text-ink">تسجيل الدخول</h1>
      <p className="mb-5 text-body text-muted">أهلًا بعودتك.</p>

      <form
        className="space-y-3"
        onSubmit={(e) => {
          e.preventDefault();
          setError(null);
          mutation.mutate();
        }}
      >
        <div>
          <label className="label">البريد الإلكتروني</label>
          <input
            type="email"
            className="field"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
          />
        </div>
        <div>
          <label className="label">كلمة المرور</label>
          <input
            type="password"
            className="field"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
          />
        </div>

        {error && <p className="field-error">{error}</p>}

        <button className="btn w-full" disabled={mutation.isPending}>
          {mutation.isPending ? '…' : 'دخول'}
        </button>
      </form>

      <p className="mt-4 text-center text-body text-muted">
        ليس لديك حساب؟{' '}
        <Link to="/register" className="font-semibold text-accent">
          أنشئ حسابًا
        </Link>
      </p>
    </div>
  );
}

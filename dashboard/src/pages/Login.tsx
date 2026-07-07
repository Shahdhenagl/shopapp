import { useForm } from 'react-hook-form';
import { useNavigate } from 'react-router-dom';
import { useState } from 'react';
import { Globe } from 'lucide-react';
import { adminAuthService, getErrorMessage } from '@/api';
import { useAuthStore } from '@/store/auth';
import { useLocaleStore } from '@/store/locale';
import { Button } from '@/components/Button';
import { MOCK_ADMIN } from '@/mock/data';
import { USE_MOCK } from '@/lib/config';

interface FormValues {
  email: string;
  password: string;
}

export function Login() {
  const navigate = useNavigate();
  const setSession = useAuthStore((s) => s.setSession);
  const { t, locale, toggleLocale } = useLocaleStore();
  const [serverError, setServerError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    defaultValues: USE_MOCK
      ? { email: MOCK_ADMIN.email, password: MOCK_ADMIN.password }
      : { email: '', password: '' },
  });

  const onSubmit = handleSubmit(async (values) => {
    setServerError(null);
    try {
      const auth = await adminAuthService.login(values);
      setSession(auth);
      navigate('/', { replace: true });
    } catch (err) {
      setServerError(getErrorMessage(err, 'Invalid credentials'));
    }
  });

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-brand-800 to-brand-900 p-4">
      <div className="card w-full max-w-md p-8">
        <div className="mb-8 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-700 text-xl font-bold text-white">
              M
            </div>
            <div>
              <h1 className="text-xl font-bold">MODIST</h1>
              <p className="text-xs text-slate-500">Admin Panel</p>
            </div>
          </div>
          <button
            onClick={toggleLocale}
            className="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
          >
            <Globe size={16} />
            {locale === 'en' ? 'EN' : 'AR'}
          </button>
        </div>

        <form onSubmit={onSubmit} className="space-y-4" noValidate>
          <div>
            <label className="label" htmlFor="email">
              {t('email')}
            </label>
            <input
              id="email"
              type="email"
              className="input"
              autoComplete="username"
              {...register('email', {
                required: 'Email is required',
                pattern: {
                  value: /^[^@\s]+@[^@\s]+\.[^@\s]+$/,
                  message: 'Enter a valid email',
                },
              })}
            />
            {errors.email && <p className="field-error">{errors.email.message}</p>}
          </div>

          <div>
            <label className="label" htmlFor="password">
              {t('password')}
            </label>
            <input
              id="password"
              type="password"
              className="input"
              autoComplete="current-password"
              {...register('password', {
                required: 'Password is required',
                minLength: { value: 4, message: 'Too short' },
              })}
            />
            {errors.password && (
              <p className="field-error">{errors.password.message}</p>
            )}
          </div>

          {serverError && (
            <div className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600 dark:bg-red-900/30 dark:text-red-300">
              {serverError}
            </div>
          )}

          <Button type="submit" loading={isSubmitting} className="w-full">
            {t('sign_in')}
          </Button>
        </form>

        {USE_MOCK && (
          <div className="mt-6 rounded-lg border border-dashed border-slate-300 p-3 text-xs text-slate-500 dark:border-slate-700">
            <p className="font-semibold">Mock mode is ON</p>
            <p>
              {MOCK_ADMIN.email} / {MOCK_ADMIN.password}
            </p>
          </div>
        )}
      </div>
    </div>
  );
}

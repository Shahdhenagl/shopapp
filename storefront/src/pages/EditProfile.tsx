import { useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Camera } from 'lucide-react';
import { auth, getErrorMessage } from '@/api';
import { Loading } from '@/components/States';
import { useAuth } from '@/store/auth';
import { useLocale } from '@/store/locale';

export function EditProfile() {
  const t = useLocale((s) => s.t);
  const navigate = useNavigate();
  const qc = useQueryClient();
  const setUser = useAuth((s) => s.setUser);
  const fileRef = useRef<HTMLInputElement>(null);

  const [form, setForm] = useState<{ name: string; phone: string } | null>(null);
  const [error, setError] = useState<string | null>(null);

  const meQuery = useQuery({
    queryKey: ['me'],
    queryFn: () => auth.me(),
  });

  // Seed the form from the server copy once.
  const me = meQuery.data;
  if (me && form === null) {
    setForm({ name: me.name ?? '', phone: me.phone ?? '' });
  }

  const adopt = (user: Parameters<typeof setUser>[0]) => {
    qc.setQueryData(['me'], user);
    setUser(user);
  };

  const saveMutation = useMutation({
    mutationFn: () =>
      auth.updateProfile({
        name: form!.name.trim(),
        phone: form!.phone.trim() || null,
      }),
    onSuccess: (user) => {
      adopt(user);
      navigate('/account');
    },
    onError: (e) => setError(getErrorMessage(e)),
  });

  const avatarMutation = useMutation({
    mutationFn: (file: File) => auth.uploadAvatar(file),
    onSuccess: adopt,
    onError: (e) => setError(getErrorMessage(e)),
  });

  if (meQuery.isLoading || !form) return <Loading />;

  return (
    <div className="mx-auto max-w-md">
      <h1 className="mb-4 text-title font-bold text-ink">{t('edit_profile')}</h1>

      {/* Avatar — one call uploads and returns the updated user. */}
      <div className="mb-5 flex justify-center">
        <button
          type="button"
          onClick={() => fileRef.current?.click()}
          className="relative"
          aria-label={t('edit_profile')}
        >
          {me?.avatar_url ? (
            <img
              src={me.avatar_url}
              alt=""
              className="h-24 w-24 rounded-pill object-cover"
            />
          ) : (
            <span className="grid h-24 w-24 place-items-center rounded-pill bg-surface-variant text-title font-bold text-muted">
              {form.name[0] ?? '·'}
            </span>
          )}
          <span className="absolute bottom-0 end-0 grid h-8 w-8 place-items-center rounded-pill bg-primary text-on-primary">
            <Camera size={14} />
          </span>
        </button>
        <input
          ref={fileRef}
          type="file"
          accept="image/*"
          className="hidden"
          onChange={(e) => {
            const file = e.target.files?.[0];
            if (file) avatarMutation.mutate(file);
          }}
        />
      </div>

      <form
        className="card space-y-3 p-4"
        onSubmit={(e) => {
          e.preventDefault();
          setError(null);
          saveMutation.mutate();
        }}
      >
        <div>
          <label className="label">{t('name')}</label>
          <input
            className="field"
            value={form.name}
            onChange={(e) => setForm({ ...form, name: e.target.value })}
            required
          />
        </div>
        <div>
          <label className="label">{t('phone')}</label>
          <input
            className="field"
            dir="ltr"
            value={form.phone}
            onChange={(e) => setForm({ ...form, phone: e.target.value })}
          />
        </div>
        <div>
          <label className="label">{t('email')}</label>
          {/* Email changes aren't part of PATCH /me. */}
          <input className="field opacity-60" value={me?.email ?? ''} disabled />
        </div>

        {error && <p className="field-error">{error}</p>}

        <button
          className="btn w-full"
          disabled={saveMutation.isPending || avatarMutation.isPending}
        >
          {t('save')}
        </button>
      </form>
    </div>
  );
}

import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { MapPin, Plus, Star, Trash2 } from 'lucide-react';
import { account, getErrorMessage } from '@/api';
import { Empty, ErrorState, Loading } from '@/components/States';
import { useLocale } from '@/store/locale';
import type { Address } from '@/types';

const EMPTY = { label: '', address: '', city: '', area: '', phone: '' };

export function Addresses() {
  const t = useLocale((s) => s.t);
  const qc = useQueryClient();
  const [form, setForm] = useState(EMPTY);
  const [adding, setAdding] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const query = useQuery({
    queryKey: ['addresses'],
    queryFn: () => account.addresses(),
  });

  const refresh = () => qc.invalidateQueries({ queryKey: ['addresses'] });

  const addMutation = useMutation({
    mutationFn: () =>
      account.addAddress({
        label: form.label.trim() || null,
        address: form.address.trim(),
        city: form.city.trim() || null,
        area: form.area.trim() || null,
        phone: form.phone.trim() || null,
      }),
    onSuccess: () => {
      refresh();
      setForm(EMPTY);
      setAdding(false);
      setError(null);
    },
    onError: (e) => setError(getErrorMessage(e)),
  });

  const removeMutation = useMutation({
    mutationFn: (id: string) => account.removeAddress(id),
    onSuccess: refresh,
    onError: (e) => setError(getErrorMessage(e)),
  });

  const defaultMutation = useMutation({
    mutationFn: (id: string) => account.makeDefault(id),
    onSuccess: refresh,
    onError: (e) => setError(getErrorMessage(e)),
  });

  const set = (key: keyof typeof form, value: string) =>
    setForm((prev) => ({ ...prev, [key]: value }));

  const lines = (a: Address) =>
    [a.address, a.area, a.city].filter(Boolean).join(' · ');

  return (
    <div className="mx-auto max-w-2xl">
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-title font-bold text-ink">{t('addresses')}</h1>
        <button
          onClick={() => setAdding((v) => !v)}
          className="flex items-center gap-1.5 rounded-pill px-3 py-2 text-caption font-semibold text-accent hover:bg-surface-variant"
        >
          <Plus size={15} /> {t('add_address')}
        </button>
      </div>

      {adding && (
        <form
          className="card mb-4 space-y-3 p-4"
          onSubmit={(e) => {
            e.preventDefault();
            setError(null);
            addMutation.mutate();
          }}
        >
          <div className="grid grid-cols-2 gap-3">
            <input
              className="field"
              placeholder={t('name')}
              value={form.label}
              onChange={(e) => set('label', e.target.value)}
            />
            <input
              className="field"
              dir="ltr"
              placeholder={t('phone')}
              value={form.phone}
              onChange={(e) => set('phone', e.target.value)}
            />
          </div>
          <input
            className="field"
            placeholder={t('address')}
            value={form.address}
            onChange={(e) => set('address', e.target.value)}
            required
          />
          <div className="grid grid-cols-2 gap-3">
            <input
              className="field"
              placeholder={t('city')}
              value={form.city}
              onChange={(e) => set('city', e.target.value)}
            />
            <input
              className="field"
              placeholder={t('area')}
              value={form.area}
              onChange={(e) => set('area', e.target.value)}
            />
          </div>
          {error && <p className="field-error">{error}</p>}
          <button className="btn w-full" disabled={addMutation.isPending}>
            {t('save')}
          </button>
        </form>
      )}

      {query.isLoading ? (
        <Loading />
      ) : query.error ? (
        <ErrorState
          message={getErrorMessage(query.error)}
          onRetry={() => query.refetch()}
        />
      ) : (query.data ?? []).length === 0 ? (
        <Empty label={t('no_addresses')} />
      ) : (
        <ul className="space-y-3">
          {query.data!.map((a) => (
            <li key={a.id} className="card flex items-start gap-3 p-4">
              <MapPin size={16} className="mt-0.5 flex-none text-muted" />
              <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                  {a.label && (
                    <span className="text-body font-semibold text-ink">
                      {a.label}
                    </span>
                  )}
                  {a.is_default && (
                    <span className="chip chip--success">
                      {t('default_address')}
                    </span>
                  )}
                </div>
                <p className="text-body text-muted">{lines(a)}</p>
                {a.phone && (
                  <p dir="ltr" className="text-caption text-hint">
                    {a.phone}
                  </p>
                )}
              </div>
              <div className="flex flex-none items-center gap-1">
                {!a.is_default && (
                  <button
                    onClick={() => defaultMutation.mutate(a.id)}
                    title={t('set_default')}
                    aria-label={t('set_default')}
                    className="rounded-pill p-2 text-muted hover:bg-surface-variant"
                  >
                    <Star size={15} />
                  </button>
                )}
                <button
                  onClick={() => removeMutation.mutate(a.id)}
                  title={t('delete')}
                  aria-label={t('delete')}
                  className="rounded-pill p-2 text-danger hover:bg-danger-surface"
                >
                  <Trash2 size={15} />
                </button>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

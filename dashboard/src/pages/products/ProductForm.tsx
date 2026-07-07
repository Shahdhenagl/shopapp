import { useForm, Controller } from 'react-hook-form';
import { useRef, useState } from 'react';
import { Plus, Star, Trash2, Upload } from 'lucide-react';
import { Button } from '@/components/Button';
import { uploadMedia, getErrorMessage } from '@/api';
import { toast } from '@/store/toast';
import { hexArgbToCss } from '@/lib/format';
import type { AdminProduct, AdminProductInput, CategoryNode } from '@/types';

const ALL_SIZES = ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

// Default ARGB hex used when adding a new color (opaque).
const DEFAULT_HEX = '#FF1B2A4A';

function cssToArgb(css: string): string {
  const h = css.replace('#', '');
  return `#FF${h.toUpperCase()}`;
}

interface FormValues {
  name_en: string;
  name_ar: string;
  style_en: string;
  style_ar: string;
  description_en: string;
  description_ar: string;
  price: number;
  currency: string;
  category_id: string;
  rating: number;
  is_newest: boolean;
}

interface ProductFormProps {
  initial?: AdminProduct;
  /** Leaf categories only (is_leaf === true). */
  categories: CategoryNode[];
  submitting?: boolean;
  onSubmit: (input: AdminProductInput) => void;
  onCancel: () => void;
}

export function ProductForm({
  initial,
  categories,
  submitting,
  onSubmit,
  onCancel,
}: ProductFormProps) {
  const fileRef = useRef<HTMLInputElement>(null);
  const [images, setImages] = useState<string[]>(initial?.images ?? []);
  const [colors, setColors] = useState<string[]>(initial?.colors ?? []);
  const [sizes, setSizes] = useState<string[]>(initial?.sizes ?? []);
  const [newImage, setNewImage] = useState('');
  const [uploading, setUploading] = useState(false);

  const {
    register,
    handleSubmit,
    control,
    formState: { errors },
  } = useForm<FormValues>({
    defaultValues: {
      name_en: initial?.name.en ?? '',
      name_ar: initial?.name.ar ?? '',
      style_en: initial?.style.en ?? '',
      style_ar: initial?.style.ar ?? '',
      description_en: initial?.description.en ?? '',
      description_ar: initial?.description.ar ?? '',
      price: initial?.price ?? 0,
      currency: initial?.currency ?? 'EGP',
      category_id: initial?.category_id ?? categories[0]?.slug ?? '',
      rating: initial?.rating ?? 0,
      is_newest: initial?.is_newest ?? false,
    },
  });

  const onPickFiles = async (files: FileList | null) => {
    if (!files || files.length === 0) return;
    setUploading(true);
    try {
      for (const file of Array.from(files)) {
        const url = await uploadMedia(file);
        setImages((prev) => [...prev, url]);
      }
    } catch (e) {
      toast.error(getErrorMessage(e));
    } finally {
      setUploading(false);
    }
  };

  const submit = handleSubmit((v) => {
    const input: AdminProductInput = {
      name: { en: v.name_en.trim(), ar: v.name_ar.trim() },
      style: { en: v.style_en.trim(), ar: v.style_ar.trim() },
      description: { en: v.description_en.trim(), ar: v.description_ar.trim() },
      price: Number(v.price),
      currency: v.currency.trim() || 'EGP',
      is_newest: v.is_newest,
      rating: Number(v.rating),
      category_id: v.category_id,
      images: images.filter(Boolean),
      sizes,
      colors,
    };
    onSubmit(input);
  });

  const toggleSize = (s: string) =>
    setSizes((prev) =>
      prev.includes(s) ? prev.filter((x) => x !== s) : [...prev, s],
    );

  return (
    <form id="product-form" onSubmit={submit} className="space-y-5">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label className="label">Name (EN)</label>
          <input
            className="input"
            {...register('name_en', { required: 'Name (EN) is required' })}
          />
          {errors.name_en && <p className="field-error">{errors.name_en.message}</p>}
        </div>
        <div>
          <label className="label">Name (AR)</label>
          <input
            className="input"
            dir="rtl"
            {...register('name_ar', { required: 'Name (AR) is required' })}
          />
          {errors.name_ar && <p className="field-error">{errors.name_ar.message}</p>}
        </div>
        <div>
          <label className="label">Style (EN)</label>
          <input className="input" {...register('style_en')} />
        </div>
        <div>
          <label className="label">Style (AR)</label>
          <input className="input" dir="rtl" {...register('style_ar')} />
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label className="label">Description (EN)</label>
          <textarea className="input" rows={3} {...register('description_en')} />
        </div>
        <div>
          <label className="label">Description (AR)</label>
          <textarea className="input" dir="rtl" rows={3} {...register('description_ar')} />
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div>
          <label className="label">Price</label>
          <input
            type="number"
            step="0.01"
            className="input"
            {...register('price', {
              required: 'Required',
              min: { value: 0, message: '≥ 0' },
            })}
          />
          {errors.price && <p className="field-error">{errors.price.message}</p>}
        </div>
        <div>
          <label className="label">Currency</label>
          <input className="input" {...register('currency')} />
        </div>
        <div>
          <label className="label">Category (leaf)</label>
          <select
            className="input"
            {...register('category_id', { required: 'Required' })}
          >
            {categories.length === 0 && <option value="">No leaf categories</option>}
            {categories.map((c) => (
              <option key={c.id} value={c.slug}>
                {c.name.en} ({c.slug})
              </option>
            ))}
          </select>
          {errors.category_id && (
            <p className="field-error">{errors.category_id.message}</p>
          )}
        </div>
        <div>
          <label className="label">Rating</label>
          <input
            type="number"
            step="0.1"
            min="0"
            max="5"
            className="input"
            {...register('rating')}
          />
        </div>
      </div>

      {/* Images gallery — first image is the primary. */}
      <div>
        <label className="label">Images (first = primary)</label>
        <div className="space-y-2">
          {images.map((url, i) => (
            <div key={`${url}-${i}`} className="flex items-center gap-2">
              <div className="relative">
                <img
                  src={url}
                  alt=""
                  className="h-10 w-10 rounded object-cover"
                  onError={(e) => {
                    (e.target as HTMLImageElement).style.visibility = 'hidden';
                  }}
                />
                {i === 0 && (
                  <span className="absolute -end-1 -top-1 rounded-full bg-brand-700 p-0.5 text-white">
                    <Star size={10} className="fill-white" />
                  </span>
                )}
              </div>
              <input
                className="input flex-1"
                value={url}
                onChange={(e) =>
                  setImages((prev) =>
                    prev.map((u, idx) => (idx === i ? e.target.value : u)),
                  )
                }
              />
              {i !== 0 && (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  title="Make primary"
                  onClick={() =>
                    setImages((prev) => {
                      const next = [...prev];
                      const [item] = next.splice(i, 1);
                      next.unshift(item);
                      return next;
                    })
                  }
                >
                  <Star size={16} />
                </Button>
              )}
              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="text-red-500"
                onClick={() => setImages((prev) => prev.filter((_, idx) => idx !== i))}
              >
                <Trash2 size={16} />
              </Button>
            </div>
          ))}

          <div className="flex flex-wrap items-center gap-2">
            <input
              ref={fileRef}
              type="file"
              accept="image/*"
              multiple
              className="hidden"
              onChange={(e) => onPickFiles(e.target.files)}
            />
            <Button
              type="button"
              variant="outline"
              size="sm"
              loading={uploading}
              icon={<Upload size={16} />}
              onClick={() => fileRef.current?.click()}
            >
              Upload
            </Button>
            <input
              className="input flex-1 min-w-[160px]"
              placeholder="or paste an image URL…"
              value={newImage}
              onChange={(e) => setNewImage(e.target.value)}
            />
            <Button
              type="button"
              variant="outline"
              size="sm"
              icon={<Plus size={16} />}
              onClick={() => {
                if (newImage.trim()) {
                  setImages((prev) => [...prev, newImage.trim()]);
                  setNewImage('');
                }
              }}
            >
              Add
            </Button>
          </div>
        </div>
      </div>

      {/* Colors (optional, empty allowed) */}
      <div>
        <label className="label">Colors (#AARRGGBB · optional)</label>
        <div className="flex flex-wrap items-center gap-2">
          {colors.map((c, i) => (
            <div
              key={i}
              className="flex items-center gap-1 rounded-lg border border-slate-200 p-1 dark:border-slate-700"
            >
              <input
                type="color"
                value={hexArgbToCss(c)}
                onChange={(e) =>
                  setColors((prev) =>
                    prev.map((col, idx) => (idx === i ? cssToArgb(e.target.value) : col)),
                  )
                }
                className="h-7 w-7 cursor-pointer rounded"
              />
              <span className="font-mono text-xs">{c}</span>
              <button
                type="button"
                onClick={() => setColors((prev) => prev.filter((_, idx) => idx !== i))}
                className="text-slate-400 hover:text-red-500"
              >
                <Trash2 size={14} />
              </button>
            </div>
          ))}
          <Button
            type="button"
            variant="outline"
            size="sm"
            icon={<Plus size={16} />}
            onClick={() => setColors((prev) => [...prev, DEFAULT_HEX])}
          >
            Color
          </Button>
        </div>
      </div>

      {/* Sizes (optional, empty allowed) */}
      <div>
        <label className="label">Sizes (optional)</label>
        <div className="flex flex-wrap gap-2">
          {ALL_SIZES.map((s) => (
            <button
              key={s}
              type="button"
              onClick={() => toggleSize(s)}
              className={`rounded-lg border px-3 py-1.5 text-sm font-medium transition ${
                sizes.includes(s)
                  ? 'border-brand-700 bg-brand-700 text-white'
                  : 'border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'
              }`}
            >
              {s}
            </button>
          ))}
        </div>
      </div>

      <label className="flex items-center gap-2 text-sm">
        <Controller
          control={control}
          name="is_newest"
          render={({ field }) => (
            <input
              type="checkbox"
              checked={field.value}
              onChange={(e) => field.onChange(e.target.checked)}
              className="h-4 w-4 rounded"
            />
          )}
        />
        Mark as newest
      </label>

      <div className="flex justify-end gap-2 pt-2">
        <Button type="button" variant="secondary" onClick={onCancel}>
          Cancel
        </Button>
        <Button type="submit" loading={submitting}>
          Save
        </Button>
      </div>
    </form>
  );
}

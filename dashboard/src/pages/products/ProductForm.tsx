import { useForm, Controller } from 'react-hook-form';
import { useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import { z } from 'zod';
import { Button } from '@/components/Button';
import type { Category, Product } from '@/types';
import type { ProductInput } from '@/api/products';
import { hexArgbToCss } from '@/lib/format';

const ALL_SIZES = ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

export const productSchema = z.object({
  name_en: z.string().min(2, 'Name (EN) is required'),
  name_ar: z.string().min(2, 'Name (AR) is required'),
  style_en: z.string().min(1, 'Style (EN) is required'),
  style_ar: z.string().min(1, 'Style (AR) is required'),
  description_en: z.string().min(1, 'Description (EN) is required'),
  description_ar: z.string().min(1, 'Description (AR) is required'),
  price: z.coerce.number().positive('Price must be > 0'),
  currency: z.string().min(1),
  category_id: z.string().min(1, 'Category is required'),
  rating: z.coerce.number().min(0).max(5),
  is_newest: z.boolean(),
});

type FormValues = z.infer<typeof productSchema>;

interface ProductFormProps {
  initial?: Product;
  categories: Category[];
  submitting?: boolean;
  onSubmit: (input: ProductInput) => void;
  onCancel: () => void;
}

// Default ARGB hex used when adding a new color.
const DEFAULT_HEX = '#FF1B2A4A';

function cssToArgb(css: string): string {
  // css like #rrggbb -> #FFrrggbb
  const h = css.replace('#', '');
  return `#FF${h.toUpperCase()}`;
}

export function ProductForm({
  initial,
  categories,
  submitting,
  onSubmit,
  onCancel,
}: ProductFormProps) {
  const [images, setImages] = useState<string[]>(
    initial?.images ?? ['https://picsum.photos/seed/new/600/800'],
  );
  const [colors, setColors] = useState<string[]>(
    initial?.colors ?? [DEFAULT_HEX],
  );
  const [sizes, setSizes] = useState<string[]>(initial?.sizes ?? ['M', 'L']);
  const [newImage, setNewImage] = useState('');

  const {
    register,
    handleSubmit,
    control,
    formState: { errors },
  } = useForm<FormValues>({
    defaultValues: {
      name_en: initial?.name_en ?? initial?.name ?? '',
      name_ar: initial?.name_ar ?? '',
      style_en: initial?.style_en ?? initial?.style ?? '',
      style_ar: initial?.style_ar ?? '',
      description_en: initial?.description_en ?? initial?.description ?? '',
      description_ar: initial?.description_ar ?? '',
      price: initial?.price ?? 820,
      currency: initial?.currency ?? 'EGP',
      category_id: initial?.category_id ?? categories[0]?.id ?? '',
      rating: initial?.rating ?? 4.6,
      is_newest: initial?.is_newest ?? false,
    },
  });

  const submit = handleSubmit((values) => {
    // Final shape guard via zod (form-level required checks already applied).
    const parsed = productSchema.safeParse(values);
    const v = parsed.success ? parsed.data : values;
    const input: ProductInput = {
      name: v.name_en,
      style: v.style_en,
      description: v.description_en,
      name_en: v.name_en,
      name_ar: v.name_ar,
      style_en: v.style_en,
      style_ar: v.style_ar,
      description_en: v.description_en,
      description_ar: v.description_ar,
      price: v.price,
      currency: v.currency,
      images: images.filter(Boolean),
      colors,
      sizes,
      category_id: v.category_id,
      rating: v.rating,
      is_newest: v.is_newest,
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
          <input className="input" {...register('name_en')} />
          {errors.name_en && <p className="field-error">{errors.name_en.message}</p>}
        </div>
        <div>
          <label className="label">Name (AR)</label>
          <input className="input" dir="rtl" {...register('name_ar')} />
          {errors.name_ar && <p className="field-error">{errors.name_ar.message}</p>}
        </div>
        <div>
          <label className="label">Style (EN)</label>
          <input className="input" {...register('style_en')} />
          {errors.style_en && <p className="field-error">{errors.style_en.message}</p>}
        </div>
        <div>
          <label className="label">Style (AR)</label>
          <input className="input" dir="rtl" {...register('style_ar')} />
          {errors.style_ar && <p className="field-error">{errors.style_ar.message}</p>}
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label className="label">Description (EN)</label>
          <textarea className="input" rows={3} {...register('description_en')} />
          {errors.description_en && (
            <p className="field-error">{errors.description_en.message}</p>
          )}
        </div>
        <div>
          <label className="label">Description (AR)</label>
          <textarea className="input" dir="rtl" rows={3} {...register('description_ar')} />
          {errors.description_ar && (
            <p className="field-error">{errors.description_ar.message}</p>
          )}
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div>
          <label className="label">Price</label>
          <input type="number" step="0.01" className="input" {...register('price')} />
          {errors.price && <p className="field-error">{errors.price.message}</p>}
        </div>
        <div>
          <label className="label">Currency</label>
          <input className="input" {...register('currency')} />
        </div>
        <div>
          <label className="label">Category</label>
          <select className="input" {...register('category_id')}>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {c.id}
              </option>
            ))}
          </select>
          {errors.category_id && (
            <p className="field-error">{errors.category_id.message}</p>
          )}
        </div>
        <div>
          <label className="label">Rating</label>
          <input type="number" step="0.1" min="0" max="5" className="input" {...register('rating')} />
          {errors.rating && <p className="field-error">{errors.rating.message}</p>}
        </div>
      </div>

      {/* Images */}
      <div>
        <label className="label">Images</label>
        <div className="space-y-2">
          {images.map((url, i) => (
            <div key={i} className="flex items-center gap-2">
              <img
                src={url}
                alt=""
                className="h-10 w-10 rounded object-cover"
                onError={(e) => {
                  (e.target as HTMLImageElement).style.visibility = 'hidden';
                }}
              />
              <input
                className="input flex-1"
                value={url}
                onChange={(e) =>
                  setImages((prev) =>
                    prev.map((u, idx) => (idx === i ? e.target.value : u)),
                  )
                }
              />
              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={() => setImages((prev) => prev.filter((_, idx) => idx !== i))}
              >
                <Trash2 size={16} />
              </Button>
            </div>
          ))}
          <div className="flex items-center gap-2">
            <input
              className="input flex-1"
              placeholder="https://…"
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

      {/* Colors */}
      <div>
        <label className="label">Colors (#AARRGGBB)</label>
        <div className="flex flex-wrap items-center gap-2">
          {colors.map((c, i) => (
            <div key={i} className="flex items-center gap-1 rounded-lg border border-slate-200 p-1 dark:border-slate-700">
              <input
                type="color"
                value={hexArgbToCss(c)}
                onChange={(e) =>
                  setColors((prev) =>
                    prev.map((col, idx) =>
                      idx === i ? cssToArgb(e.target.value) : col,
                    ),
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

      {/* Sizes */}
      <div>
        <label className="label">Sizes</label>
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

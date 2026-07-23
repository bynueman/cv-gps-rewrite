import { useMemo, useState, type FormEvent, type ReactElement } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import AdminLayout from "@/Layouts/AdminLayout";
import { ImageUploadField, type UploadedImage } from "@/Components/admin/ImageUploadField";
import { useUnsavedChangesGuard } from "@/hooks/useUnsavedChangesGuard";
import { slugify } from "@/lib/slug";

const inputClass =
  "w-full rounded-xl border border-espresso-900/15 bg-cream-50 px-4 py-3 text-sm text-espresso-900 outline-none transition-colors focus:border-gold-500 focus:ring-2 focus:ring-gold-400/30";
const labelClass = "mb-1.5 block text-sm font-semibold";

type Brand = { id: number; key: string; name: string };

type ProductFormValues = {
  id: number;
  brand_id: number;
  slug: string;
  group: string | null;
  packaging: string | null;
  name: { id: string; en: string };
  short: { id: string; en: string };
  personality: { id: string; en: string };
  serving: { id: string; en: string };
  description: { id: string; en: string };
  highlights: { id: string; en: string };
  notes: { id: string; en: string };
  color: string;
  color_dark: string;
  image: string | null;
  image_thumb: string | null;
  image_og: string | null;
  featured: boolean;
  placeholder: boolean;
  is_active: boolean;
  sort_order: number;
};

function Form({
  mode,
  brands,
  product,
}: {
  mode: "create" | "edit";
  brands: Brand[];
  product?: ProductFormValues;
}) {
  const [tab, setTab] = useState<"id" | "en">("id");
  const [slugTouched, setSlugTouched] = useState(mode === "edit");

  const { data, setData, post, patch, processing, errors, transform, isDirty } = useForm({
    brand_id: product?.brand_id ?? brands[0]?.id ?? 0,
    slug: product?.slug ?? "",
    group: product?.group ?? "",
    packaging: product?.packaging ?? "",
    name: { id: product?.name.id ?? "", en: product?.name.en ?? "" },
    short: { id: product?.short.id ?? "", en: product?.short.en ?? "" },
    personality: { id: product?.personality.id ?? "", en: product?.personality.en ?? "" },
    serving: { id: product?.serving.id ?? "", en: product?.serving.en ?? "" },
    description: { id: product?.description.id ?? "", en: product?.description.en ?? "" },
    highlights: { id: product?.highlights.id ?? "", en: product?.highlights.en ?? "" },
    notes: { id: product?.notes.id ?? "", en: product?.notes.en ?? "" },
    color: product?.color ?? "#E29A12",
    color_dark: product?.color_dark ?? "#9C6508",
    image: product?.image ?? (null as string | null),
    image_thumb: product?.image_thumb ?? (null as string | null),
    image_og: product?.image_og ?? (null as string | null),
    featured: product?.featured ?? false,
    placeholder: product?.placeholder ?? false,
    is_active: product?.is_active ?? true,
    sort_order: product?.sort_order ?? 0,
  });

  const { bypassNext } = useUnsavedChangesGuard(isDirty);

  const selectedBrand = brands.find((b) => b.id === data.brand_id);
  const isTeko = selectedBrand?.key === "putri-teko";

  const effectiveSlug = useMemo(
    () => (slugTouched ? data.slug : slugify(data.name.id)),
    [slugTouched, data.slug, data.name.id]
  );

  function handleImageChange(value: UploadedImage) {
    setData((prev) => ({ ...prev, image: value.image, image_thumb: value.imageThumb, image_og: value.imageOg }));
  }

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    transform((formData) => ({ ...formData, slug: effectiveSlug }));
    bypassNext();

    if (mode === "create") {
      post(route("admin.products.store"));
    } else {
      patch(route("admin.products.update", product!.id));
    }
  }

  const image: UploadedImage = { image: data.image, imageThumb: data.image_thumb, imageOg: data.image_og };

  return (
    <>
      <Head title={mode === "create" ? "Tambah Produk" : "Edit Produk"} />
      <h1 className="font-display text-2xl font-semibold">
        {mode === "create" ? "Tambah Produk" : "Edit Produk"}
      </h1>

      <form onSubmit={handleSubmit} className="mt-8 max-w-3xl space-y-6">
        <div className="grid gap-5 sm:grid-cols-2">
          <div>
            <label className={labelClass}>Brand</label>
            <select
              className={inputClass}
              value={data.brand_id}
              onChange={(e) => setData("brand_id", Number(e.target.value))}
            >
              {brands.map((b) => (
                <option key={b.id} value={b.id}>
                  {b.name}
                </option>
              ))}
            </select>
            {errors.brand_id ? <p className="mt-1.5 text-sm text-red-700">{errors.brand_id}</p> : null}
          </div>

          <div>
            <label className={labelClass}>Slug</label>
            <input
              type="text"
              value={effectiveSlug}
              onChange={(e) => {
                setSlugTouched(true);
                setData("slug", e.target.value);
              }}
              className={inputClass}
            />
            {errors.slug ? <p className="mt-1.5 text-sm text-red-700">{errors.slug}</p> : null}
          </div>
        </div>

        {isTeko ? (
          <div className="grid gap-5 sm:grid-cols-2">
            <div>
              <label className={labelClass}>Grup</label>
              <select className={inputClass} value={data.group ?? ""} onChange={(e) => setData("group", e.target.value)}>
                <option value="">—</option>
                <option value="rtd">RTD (Siap Minum)</option>
                <option value="brew">Brew (Seduh Sendiri)</option>
              </select>
            </div>
            <div>
              <label className={labelClass}>Kemasan</label>
              <select
                className={inputClass}
                value={data.packaging ?? ""}
                onChange={(e) => setData("packaging", e.target.value)}
              >
                <option value="">—</option>
                <option value="botol">Botol</option>
                <option value="kotak">Kotak Sachet</option>
                <option value="toples">Toples</option>
                <option value="kemasan">Kemasan</option>
                <option value="besek">Besek</option>
              </select>
            </div>
          </div>
        ) : null}

        {/* Language tabs */}
        <div className="flex gap-2 border-b border-espresso-900/10">
          {(["id", "en"] as const).map((l) => (
            <button
              key={l}
              type="button"
              onClick={() => setTab(l)}
              className={`px-4 py-2 text-sm font-semibold ${
                tab === l ? "border-b-2 border-gold-600 text-espresso-950" : "text-espresso-500"
              }`}
            >
              {l === "id" ? "Indonesia" : "English"}
            </button>
          ))}
        </div>

        <div className={tab === "id" ? "space-y-5" : "hidden"}>
          <div>
            <label className={labelClass}>Nama (ID)</label>
            <input
              type="text"
              value={data.name.id}
              onChange={(e) => setData("name", { ...data.name, id: e.target.value })}
              className={inputClass}
            />
            {errors["name.id" as keyof typeof errors] ? (
              <p className="mt-1.5 text-sm text-red-700">{errors["name.id" as keyof typeof errors]}</p>
            ) : null}
          </div>
          <div>
            <label className={labelClass}>Tagline Singkat (ID)</label>
            <input
              type="text"
              value={data.short.id}
              onChange={(e) => setData("short", { ...data.short, id: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Kepribadian Rasa (ID)</label>
            <input
              type="text"
              value={data.personality.id}
              onChange={(e) => setData("personality", { ...data.personality, id: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Penyajian (ID, opsional)</label>
            <input
              type="text"
              value={data.serving.id}
              onChange={(e) => setData("serving", { ...data.serving, id: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Deskripsi (ID)</label>
            <textarea
              rows={4}
              value={data.description.id}
              onChange={(e) => setData("description", { ...data.description, id: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Keunggulan (ID) — satu per baris</label>
            <textarea
              rows={4}
              value={data.highlights.id}
              onChange={(e) => setData("highlights", { ...data.highlights, id: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Catatan Rasa/Bahan (ID) — satu per baris</label>
            <textarea
              rows={4}
              value={data.notes.id}
              onChange={(e) => setData("notes", { ...data.notes, id: e.target.value })}
              className={inputClass}
            />
          </div>
        </div>

        <div className={tab === "en" ? "space-y-5" : "hidden"}>
          <div>
            <label className={labelClass}>Name (EN)</label>
            <input
              type="text"
              value={data.name.en}
              onChange={(e) => setData("name", { ...data.name, en: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Short Tagline (EN)</label>
            <input
              type="text"
              value={data.short.en}
              onChange={(e) => setData("short", { ...data.short, en: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Flavor Personality (EN)</label>
            <input
              type="text"
              value={data.personality.en}
              onChange={(e) => setData("personality", { ...data.personality, en: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Serving (EN, optional)</label>
            <input
              type="text"
              value={data.serving.en}
              onChange={(e) => setData("serving", { ...data.serving, en: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Description (EN)</label>
            <textarea
              rows={4}
              value={data.description.en}
              onChange={(e) => setData("description", { ...data.description, en: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Highlights (EN) — one per line</label>
            <textarea
              rows={4}
              value={data.highlights.en}
              onChange={(e) => setData("highlights", { ...data.highlights, en: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Flavor/Ingredient Notes (EN) — one per line</label>
            <textarea
              rows={4}
              value={data.notes.en}
              onChange={(e) => setData("notes", { ...data.notes, en: e.target.value })}
              className={inputClass}
            />
          </div>
        </div>

        <div className="grid gap-5 sm:grid-cols-2">
          <div>
            <label className={labelClass}>Warna Terang</label>
            <input
              type="color"
              value={data.color}
              onChange={(e) => setData("color", e.target.value)}
              className="h-11 w-full rounded-xl border border-espresso-900/15 bg-cream-50"
            />
          </div>
          <div>
            <label className={labelClass}>Warna Gelap</label>
            <input
              type="color"
              value={data.color_dark}
              onChange={(e) => setData("color_dark", e.target.value)}
              className="h-11 w-full rounded-xl border border-espresso-900/15 bg-cream-50"
            />
          </div>
        </div>

        <div>
          <label className={labelClass}>Gambar Produk</label>
          <ImageUploadField initial={image} onChange={handleImageChange} />
        </div>

        <div>
          <label className={labelClass}>Urutan Tampil</label>
          <input
            type="number"
            value={data.sort_order}
            onChange={(e) => setData("sort_order", Number(e.target.value))}
            className={`${inputClass} max-w-[10rem]`}
          />
        </div>

        <div className="flex flex-wrap gap-6">
          <label className="flex items-center gap-2 text-sm font-semibold">
            <input type="checkbox" checked={data.is_active} onChange={(e) => setData("is_active", e.target.checked)} />
            Aktif (tampil di situs)
          </label>
          <label className="flex items-center gap-2 text-sm font-semibold">
            <input type="checkbox" checked={data.featured} onChange={(e) => setData("featured", e.target.checked)} />
            Featured
          </label>
        </div>

        <div className="flex items-center gap-4 pt-2">
          <button type="submit" disabled={processing} className="btn-primary disabled:opacity-60">
            {processing ? "Menyimpan…" : "Simpan"}
          </button>
          <Link href={route("admin.products.index")} className="btn-outline">
            Batal
          </Link>
        </div>
      </form>
    </>
  );
}

Form.layout = (page: ReactElement<{ mode: "create" | "edit" }>) => (
  <AdminLayout title={page.props.mode === "create" ? "Tambah Produk" : "Edit Produk"} children={page} />
);

export default Form;

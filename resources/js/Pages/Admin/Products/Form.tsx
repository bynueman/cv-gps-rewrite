import { useMemo, useRef, useState, type FormEvent, type ReactElement } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import AdminLayout from "@/Layouts/AdminLayout";
import { ImageUploadField, type UploadedImage } from "@/Components/admin/ImageUploadField";
import { BilingualListEditor, type BilingualItem } from "@/Components/admin/BilingualListEditor";
import { TranslationPanel, type TranslationState } from "@/Components/admin/TranslationPanel";
import { useUnsavedChangesGuard } from "@/hooks/useUnsavedChangesGuard";
import { slugify } from "@/lib/slug";
const inputClass = "mt-2 w-full rounded-xl border border-espresso-900/20 bg-cream-50 px-4 py-3 text-sm";
const labelClass = "block text-sm font-semibold";
const textFields = ["name", "short", "personality", "serving", "description"] as const;
const labels = {
  name: "Nama produk",
  short: "Tagline singkat",
  personality: "Kepribadian rasa",
  serving: "Penyajian (opsional)",
  description: "Deskripsi"
};
type Pair = {
  id: string;
  en: string;
};
type Brand = {
  id: number;
  key: string;
  name: string;
};
type ProductFormValues = {
  id: number;
  brand_id: number;
  slug: string;
  group: string | null;
  packaging: string | null;
  name: Pair;
  short: Pair;
  personality: Pair;
  serving: Pair;
  description: Pair;
  highlights: BilingualItem[];
  notes: BilingualItem[];
  color: string;
  color_dark: string;
  image: string | null;
  image_thumb: string | null;
  image_og: string | null;
  shopee_url: string | null;
  net_weight: string | null;
  seo_title_id: string | null;
  seo_title_en: string | null;
  meta_description_id: string | null;
  meta_description_en: string | null;
  image_alt_id: string | null;
  image_alt_en: string | null;
  featured: boolean;
  placeholder: boolean;
  is_active: boolean;
  sort_order: number;
};
function Form({
  mode,
  brands,
  product,
  translation
}: {
  mode: "create" | "edit";
  brands: Brand[];
  product?: ProductFormValues;
  translation?: TranslationState;
}) {
  const [slugTouched, setSlugTouched] = useState(mode === "edit");
  const [englishOpen, setEnglishOpen] = useState(false);
  const [revision, setRevision] = useState(translation?.revision ?? null);
  const {
    data,
    setData,
    post,
    patch,
    processing,
    errors,
    transform,
    isDirty,
    setDefaults
  } = useForm({
    brand_id: product?.brand_id ?? brands[0]?.id ?? 0,
    slug: product?.slug ?? "",
    group: product?.group ?? "",
    packaging: product?.packaging ?? "",
    name: product?.name ?? {
      id: "",
      en: ""
    },
    short: product?.short ?? {
      id: "",
      en: ""
    },
    personality: product?.personality ?? {
      id: "",
      en: ""
    },
    serving: product?.serving ?? {
      id: "",
      en: ""
    },
    description: product?.description ?? {
      id: "",
      en: ""
    },
    highlights: product?.highlights ?? [],
    notes: product?.notes ?? [],
    color: product?.color ?? "#E29A12",
    color_dark: product?.color_dark ?? "#9C6508",
    image: product?.image ?? null as string | null,
    image_thumb: product?.image_thumb ?? null as string | null,
    image_og: product?.image_og ?? null as string | null,
    shopee_url: product?.shopee_url ?? "",
    net_weight: product?.net_weight ?? "",
    seo_title_id: product?.seo_title_id ?? "",
    seo_title_en: product?.seo_title_en ?? "",
    meta_description_id: product?.meta_description_id ?? "",
    meta_description_en: product?.meta_description_en ?? "",
    image_alt_id: product?.image_alt_id ?? "",
    image_alt_en: product?.image_alt_en ?? "",
    featured: product?.featured ?? false,
    placeholder: product?.placeholder ?? false,
    is_active: product?.is_active ?? true,
    sort_order: product?.sort_order ?? 0
  });
  const {
    bypassNext
  } = useUnsavedChangesGuard(isDirty);
  const effectiveSlug = useMemo(() => slugTouched ? data.slug : slugify(data.name.id), [slugTouched, data.slug, data.name.id]);
  function englishValues() {
    return Object.fromEntries([
      ...textFields.map(field => [field, data[field].en]),
      ...(["highlights", "notes"] as const).flatMap(list => data[list].map(row => [list+"."+row.key, row.en])),
      ...(["seo_title", "meta_description", "image_alt"] as const).map(field => [field, data[`${field}_en`]]),
    ]);
  }
  const englishSnapshot = useRef(englishValues());
  const isTeko = brands.find(brand => brand.id === data.brand_id)?.key === "putri-teko";
  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    transform(values => ({
      ...values,
      slug: effectiveSlug,
      translation_revision: revision,
      translation_english_edits: Object.entries(englishValues()).filter(([path, value]) => value !== (englishSnapshot.current[path] ?? '')).map(([path]) => path),
    }));
    bypassNext();
    if (mode === "create") post(route("admin.products.store"));else patch(route("admin.products.update", product!.id));
  }
  function loadEnglish(values: Record<string, string>) {
    const next = {
      ...data
    };
    for (const field of textFields) next[field] = {
      ...next[field],
      en: values[field] ?? next[field].en
    };
    for (const list of ["highlights", "notes"] as const) next[list] = next[list].map(row => ({
      ...row,
      en: values[list + "." + row.key] ?? row.en
    }));
    for (const field of ["seo_title", "meta_description", "image_alt"] as const) next[`${field}_en`] = values[field] ?? next[`${field}_en`];
    englishSnapshot.current = {...englishSnapshot.current, ...values};
    setData(next);
    setDefaults(next);
    setEnglishOpen(true);
  }
  const image: UploadedImage = {
    image: data.image,
    imageThumb: data.image_thumb,
    imageOg: data.image_og
  };
  function imageChange(value: UploadedImage) {
    setData(prev => ({
      ...prev,
      image: value.image,
      image_thumb: value.imageThumb,
      image_og: value.imageOg
    }));
  }
  return <>
    <Head title={mode === "create" ? "Tambah Produk" : "Edit Produk"} /><h1 className="font-display text-2xl">{mode === "create" ? "Tambah Produk" : "Edit Produk"}</h1>
    <form onSubmit={submit} className="mt-7 max-w-3xl space-y-6">
      <TranslationPanel initial={translation} dirty={isDirty} revision={revision} onRevision={setRevision} onLoadEnglish={loadEnglish} />
      {Object.keys(errors).length > 0 && <div role="alert" className="rounded-xl bg-red-50 p-4 text-sm text-red-800"><p className="font-semibold">Periksa formulir:</p>{Object.entries(errors).map(([field, error]) => <p key={field}>{field}: {error}</p>)}</div>}
      <div className="grid gap-5 sm:grid-cols-2"><label className={labelClass}>Brand<select className={inputClass} value={data.brand_id} onChange={e => setData("brand_id", Number(e.target.value))}>{brands.map(brand => <option key={brand.id} value={brand.id}>{brand.name}</option>)}</select></label><label className={labelClass}>Slug<input required className={inputClass} value={effectiveSlug} onChange={e => {
            setSlugTouched(true);
            setData("slug", e.target.value);
          }} /></label></div>
      {isTeko && <div className="grid gap-5 sm:grid-cols-2"><label className={labelClass}>Grup<select className={inputClass} value={data.group} onChange={e => setData("group", e.target.value)}><option value="">—</option><option value="rtd">RTD (Siap Minum)</option><option value="brew">Brew (Seduh Sendiri)</option></select></label><label className={labelClass}>Kemasan<select className={inputClass} value={data.packaging} onChange={e => setData("packaging", e.target.value)}><option value="">—</option>{["botol", "kotak", "toples", "kemasan", "besek"].map(value => <option key={value} value={value}>{value}</option>)}</select></label></div>}
      <section className="space-y-5"><h2 className="font-display text-xl">Konten Indonesia</h2>{textFields.map(field => <label key={field} className={labelClass}>{labels[field]}{field === "description" ? <textarea required rows={5} className={inputClass} value={data[field].id} onChange={e => setData(field, {
            ...data[field],
            id: e.target.value
          })} /> : <input required={field !== "serving"} maxLength={255} className={inputClass} value={data[field].id} onChange={e => setData(field, {
            ...data[field],
            id: e.target.value
          })} />}</label>)}</section>
      {(["highlights", "notes"] as const).map(list => <BilingualListEditor key={list} label={list === "highlights" ? "Keunggulan" : "Catatan rasa / bahan"} items={data[list]} onChange={items => setData(list, items)} errors={Object.fromEntries(Object.entries(errors).filter(([key]) => key.startsWith(list)))} />)}
      <details open={englishOpen} onToggle={e => setEnglishOpen(e.currentTarget.open)} className="rounded-2xl border border-espresso-900/15 p-5"><summary className="cursor-pointer font-semibold">Terjemahan English · pratinjau & edit manual (opsional)</summary><p className="mt-3 text-xs leading-relaxed text-espresso-600">English yang diubah di sini akan dilindungi dari penimpaan otomatis. Untuk menggantinya dengan hasil baru, gunakan tindakan terjemahkan ulang dengan konfirmasi di atas.</p><div className="mt-5 space-y-5">{textFields.map(field => <label key={field} className={labelClass}>{labels[field]} (English){field === "description" ? <textarea rows={5} className={inputClass} value={data[field].en} onChange={e => setData(field, {
              ...data[field],
              en: e.target.value
            })} /> : <input maxLength={255} className={inputClass} value={data[field].en} onChange={e => setData(field, {
              ...data[field],
              en: e.target.value
            })} />}</label>)}</div></details>
      <div className="grid gap-5 sm:grid-cols-2">{(["color", "color_dark"] as const).map(field => <label key={field} className={labelClass}>{field === "color" ? "Warna" : "Warna gelap"}<input type="color" value={data[field]} onChange={e => setData(field, e.target.value)} className="mt-2 h-11 w-full rounded-xl border border-espresso-900/15" /></label>)}</div>
      <div><p className={labelClass}>Gambar produk</p><div className="mt-2"><ImageUploadField initial={image} onChange={imageChange} context="products" /></div></div>
      <label className={labelClass}>Urutan tampil<input type="number" value={data.sort_order} onChange={e => setData("sort_order", Number(e.target.value))} className={inputClass} /></label>
      <fieldset className="space-y-4 rounded-2xl border border-espresso-900/15 p-5"><legend className="px-2 font-semibold">Marketing & Commerce</legend><label className={labelClass}>URL Shopee produk (opsional)<input type="url" className={inputClass} value={data.shopee_url} onChange={e => setData("shopee_url", e.target.value)} placeholder="https://shopee.co.id/…" /></label><label className={labelClass}>Isi bersih terverifikasi (opsional)<input className={inputClass} value={data.net_weight} onChange={e => setData("net_weight", e.target.value)} maxLength={80} /></label><p className="text-xs text-espresso-600">URL, kode produk, harga, dan berat tidak diterjemahkan atau dibuat otomatis. Klaim makanan perlu pemeriksaan manusia.</p></fieldset>
      <details className="rounded-2xl border border-espresso-900/15 p-5"><summary className="cursor-pointer font-semibold">SEO & Metadata — opsional</summary><p className="my-4 text-sm text-espresso-600">Isi Indonesia. English ikut antrean; bila kosong, metadata memakai nama/ringkasan produk. Canonical tetap mengikuti URL produk.</p><div className="grid gap-4 sm:grid-cols-2">{(["seo_title_id", "meta_description_id", "image_alt_id"] as const).map(field => <label key={field} className={labelClass}>{field.replace(/_/g, " ")}<input className={inputClass} value={data[field]} onChange={e => setData(field, e.target.value)} maxLength={field.startsWith("meta") ? 160 : field.startsWith("seo") ? 120 : 255} /></label>)}</div><details className="mt-5"><summary className="cursor-pointer text-sm font-semibold">English metadata · override manual</summary><div className="mt-4 grid gap-4 sm:grid-cols-2">{(["seo_title_en", "meta_description_en", "image_alt_en"] as const).map(field => <label key={field} className={labelClass}>{field.replace(/_/g, " ")}<input className={inputClass} value={data[field]} onChange={e => setData(field, e.target.value)} maxLength={field.startsWith("meta") ? 160 : field.startsWith("seo") ? 120 : 255} /></label>)}</div></details></details>
      <div className="flex flex-wrap gap-6">{(["is_active", "featured", "placeholder"] as const).map(field => <label key={field} className="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" checked={data[field]} onChange={e => setData(field, e.target.checked)} />{field === "is_active" ? "Aktif (tampil di situs)" : field === "featured" ? "Featured" : "Placeholder"}</label>)}</div>
      <div className="flex flex-wrap gap-4"><button type="submit" disabled={processing} className="btn-primary disabled:opacity-50">{processing ? "Menyimpan & menyiapkan English…" : "Simpan"}</button><Link href={route("admin.products.index")} className="btn-outline">Batal</Link></div>
    </form>
  </>;
}
Form.layout = (page: ReactElement) => <AdminLayout title="Produk">{page}</AdminLayout>;
export default Form;

import { useMemo, useState, type FormEvent, type ReactElement } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import AdminLayout from "@/Layouts/AdminLayout";
import { ImageUploadField, type UploadedImage } from "@/Components/admin/ImageUploadField";
import { RichTextEditor } from "@/Components/admin/RichTextEditor";
import { slugify } from "@/lib/slug";

const EXCERPT_MIN = 70;
const EXCERPT_MAX = 160;

const inputClass =
  "w-full rounded-xl border border-espresso-900/15 bg-cream-50 px-4 py-3 text-sm text-espresso-900 outline-none transition-colors focus:border-gold-500 focus:ring-2 focus:ring-gold-400/30";

type Category = { slug: string; id: string; en: string };

type ArticleFormValues = {
  id: number;
  slug: string;
  date: string;
  category_slug: string;
  title: { id: string; en: string };
  excerpt: { id: string; en: string };
  body: { id: string; en: string };
  tags: { id: string; en: string };
  image: string | null;
  image_thumb: string | null;
  image_og: string | null;
  featured: boolean;
  published: boolean;
};

async function uploadImage(file: File): Promise<string | null> {
  const form = new FormData();
  form.append("file", file);
  try {
    const res = await window.axios.post(route("admin.upload"), form);
    return res.data.image as string;
  } catch {
    return null;
  }
}

function ExcerptCounter({ value }: { value: string }) {
  const len = value.length;
  const tone =
    len === 0
      ? "text-espresso-500"
      : len < EXCERPT_MIN || len > EXCERPT_MAX
        ? "text-amber-700"
        : "text-green-700";
  return (
    <p className={`mt-1 text-xs ${tone}`}>
      {len} karakter (ideal {EXCERPT_MIN}–{EXCERPT_MAX} untuk cuplikan pencarian Google)
    </p>
  );
}

function SeoPreview({ title, url, description }: { title: string; url: string; description: string }) {
  return (
    <div className="mt-2 rounded-xl border border-espresso-900/10 bg-cream-50 p-4">
      <p className="mb-1 text-[10px] font-semibold uppercase tracking-wide text-espresso-500">
        Pratinjau hasil pencarian Google
      </p>
      <p className="truncate text-[13px] text-green-800">{url}</p>
      <p className="truncate text-lg text-blue-800">{title || "Judul artikel"}</p>
      <p className="line-clamp-2 text-sm text-espresso-600">{description || "Ringkasan artikel akan muncul di sini."}</p>
    </div>
  );
}

function Form({
  mode,
  categories,
  article,
}: {
  mode: "create" | "edit";
  categories: Category[];
  article?: ArticleFormValues;
}) {
  const [tab, setTab] = useState<"id" | "en">("id");
  const [slugTouched, setSlugTouched] = useState(mode === "edit");

  const { data, setData, post, patch, processing, errors, transform } = useForm({
    slug: article?.slug ?? "",
    date: article?.date ?? new Date().toISOString().slice(0, 10),
    category_slug: article?.category_slug ?? categories[0].slug,
    title: { id: article?.title.id ?? "", en: article?.title.en ?? "" },
    excerpt: { id: article?.excerpt.id ?? "", en: article?.excerpt.en ?? "" },
    body: { id: article?.body.id ?? "", en: article?.body.en ?? "" },
    tags: { id: article?.tags.id ?? "", en: article?.tags.en ?? "" },
    image: article?.image ?? (null as string | null),
    image_thumb: article?.image_thumb ?? (null as string | null),
    image_og: article?.image_og ?? (null as string | null),
    featured: article?.featured ?? false,
    published: article?.published ?? false,
    confirm_slug_change: false,
  });

  const effectiveSlug = useMemo(
    () => (slugTouched ? data.slug : slugify(data.title.id)),
    [slugTouched, data.slug, data.title.id]
  );

  const wasPublished = mode === "edit" && Boolean(article?.published);
  const slugChangedAfterPublish = wasPublished && effectiveSlug !== article?.slug;

  function handleImageChange(value: UploadedImage) {
    setData((prev) => ({
      ...prev,
      image: value.image,
      image_thumb: value.imageThumb,
      image_og: value.imageOg,
    }));
  }

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();

    transform((formData) => ({ ...formData, slug: effectiveSlug }));

    if (mode === "create") {
      post(route("admin.articles.store"));
    } else {
      patch(route("admin.articles.update", article!.id));
    }
  }

  const image: UploadedImage = { image: data.image, imageThumb: data.image_thumb, imageOg: data.image_og };

  return (
    <>
      <Head title={mode === "create" ? "Tambah Artikel" : "Edit Artikel"} />
      <div className="container-page py-10">
        <nav aria-label="Breadcrumb" className="text-sm text-espresso-500">
          <Link href={route("admin.dashboard")} className="hover:text-gold-700">
            Admin
          </Link>{" "}
          / <span className="text-espresso-900">{mode === "create" ? "Tambah Blog" : "Edit Blog"}</span>
        </nav>

        <h1 className="mt-3 font-display text-2xl font-semibold">
          {mode === "create" ? "Tambah Blog" : "Edit Blog"}
        </h1>

        <form onSubmit={handleSubmit} className="mt-8 max-w-3xl space-y-6">
          <div className="grid gap-5 sm:grid-cols-2">
            <div>
              <span className="mb-1.5 block text-sm font-semibold">Slug</span>
              <input
                type="text"
                value={effectiveSlug}
                onChange={(e) => {
                  setSlugTouched(true);
                  setData("slug", e.target.value);
                }}
                className={inputClass}
                placeholder="contoh-slug-artikel"
              />
              {errors.slug ? <p className="mt-1 text-xs text-red-700">{errors.slug}</p> : null}
              {slugChangedAfterPublish && !errors.slug ? (
                <label className="mt-2 flex items-start gap-2 rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-800">
                  <input
                    type="checkbox"
                    checked={data.confirm_slug_change}
                    onChange={(e) => setData("confirm_slug_change", e.target.checked)}
                    className="mt-0.5"
                  />
                  <span>
                    Artikel ini sudah dipublikasikan. Mengubah slug akan mengubah URL publik. Centang untuk
                    melanjutkan.
                  </span>
                </label>
              ) : null}
            </div>

            <div>
              <span className="mb-1.5 block text-sm font-semibold">Kategori</span>
              <select
                value={data.category_slug}
                onChange={(e) => setData("category_slug", e.target.value)}
                className={inputClass}
              >
                {categories.map((c) => (
                  <option key={c.slug} value={c.slug}>
                    {c.id}
                  </option>
                ))}
              </select>
              {errors.category_slug ? <p className="mt-1 text-xs text-red-700">{errors.category_slug}</p> : null}
            </div>
          </div>

          <div>
            <span className="mb-1.5 block text-sm font-semibold">Tanggal</span>
            <input
              type="date"
              value={data.date}
              onChange={(e) => setData("date", e.target.value)}
              className={`${inputClass} max-w-xs`}
              required
            />
            {errors.date ? <p className="mt-1 text-xs text-red-700">{errors.date}</p> : null}
          </div>

          <div>
            <span className="mb-1.5 block text-sm font-semibold">Gambar</span>
            <ImageUploadField initial={image} onChange={handleImageChange} />
          </div>

          <div className="flex gap-2 border-b border-espresso-900/10">
            {(["id", "en"] as const).map((l) => (
              <button
                key={l}
                type="button"
                onClick={() => setTab(l)}
                className={`px-4 py-2 text-sm font-semibold uppercase tracking-wide ${
                  tab === l ? "border-b-2 border-gold-500 text-espresso-950" : "text-espresso-500"
                }`}
              >
                {l === "id" ? "Indonesia" : "English"}
              </button>
            ))}
          </div>

          <div className={tab === "id" ? "space-y-5" : "hidden space-y-5"}>
            <div>
              <span className="mb-1.5 block text-sm font-semibold">Judul (ID)</span>
              <input
                type="text"
                value={data.title.id}
                onChange={(e) => setData("title", { ...data.title, id: e.target.value })}
                className={inputClass}
              />
              {errors["title.id"] ? <p className="mt-1 text-xs text-red-700">{errors["title.id"]}</p> : null}
            </div>
            <div>
              <span className="mb-1.5 block text-sm font-semibold">Ringkasan / Meta Deskripsi (ID)</span>
              <textarea
                rows={2}
                value={data.excerpt.id}
                onChange={(e) => setData("excerpt", { ...data.excerpt, id: e.target.value })}
                className={inputClass}
              />
              <ExcerptCounter value={data.excerpt.id} />
              {errors["excerpt.id"] ? <p className="mt-1 text-xs text-red-700">{errors["excerpt.id"]}</p> : null}
              <SeoPreview
                title={data.title.id}
                url={`${window.location.origin}/news/${effectiveSlug}`}
                description={data.excerpt.id}
              />
            </div>
            <div>
              <span className="mb-1.5 block text-sm font-semibold">Isi Artikel (ID)</span>
              <RichTextEditor
                value={data.body.id}
                onChange={(html) => setData("body", { ...data.body, id: html })}
                onUploadImage={uploadImage}
              />
              {errors["body.id"] ? <p className="mt-1 text-xs text-red-700">{errors["body.id"]}</p> : null}
            </div>
            <div>
              <span className="mb-1.5 block text-sm font-semibold">Tags (ID)</span>
              <input
                type="text"
                value={data.tags.id}
                onChange={(e) => setData("tags", { ...data.tags, id: e.target.value })}
                className={inputClass}
                placeholder="contoh: kuicip, singkong, resep"
              />
              <p className="mt-1 text-xs text-espresso-500">Pisahkan dengan koma. Digunakan untuk meta keywords & SEO.</p>
            </div>
          </div>

          <div className={tab === "en" ? "space-y-5" : "hidden space-y-5"}>
            <div>
              <span className="mb-1.5 block text-sm font-semibold">Title (EN)</span>
              <input
                type="text"
                value={data.title.en}
                onChange={(e) => setData("title", { ...data.title, en: e.target.value })}
                className={inputClass}
              />
              {errors["title.en"] ? <p className="mt-1 text-xs text-red-700">{errors["title.en"]}</p> : null}
            </div>
            <div>
              <span className="mb-1.5 block text-sm font-semibold">Excerpt / Meta Description (EN)</span>
              <textarea
                rows={2}
                value={data.excerpt.en}
                onChange={(e) => setData("excerpt", { ...data.excerpt, en: e.target.value })}
                className={inputClass}
              />
              <ExcerptCounter value={data.excerpt.en} />
              {errors["excerpt.en"] ? <p className="mt-1 text-xs text-red-700">{errors["excerpt.en"]}</p> : null}
              <SeoPreview
                title={data.title.en}
                url={`${window.location.origin}/news/${effectiveSlug}`}
                description={data.excerpt.en}
              />
            </div>
            <div>
              <span className="mb-1.5 block text-sm font-semibold">Body (EN)</span>
              <RichTextEditor
                value={data.body.en}
                onChange={(html) => setData("body", { ...data.body, en: html })}
                onUploadImage={uploadImage}
              />
              {errors["body.en"] ? <p className="mt-1 text-xs text-red-700">{errors["body.en"]}</p> : null}
            </div>
            <div>
              <span className="mb-1.5 block text-sm font-semibold">Tags (EN)</span>
              <input
                type="text"
                value={data.tags.en}
                onChange={(e) => setData("tags", { ...data.tags, en: e.target.value })}
                className={inputClass}
                placeholder="e.g. kuicip, cassava, recipe"
              />
              <p className="mt-1 text-xs text-espresso-500">Comma-separated. Used for meta keywords & SEO.</p>
            </div>
          </div>

          <div className="flex flex-wrap gap-6 border-t border-espresso-900/10 pt-6">
            <label className="flex items-center gap-2 text-sm font-semibold">
              <input
                type="checkbox"
                checked={data.featured}
                onChange={(e) => setData("featured", e.target.checked)}
              />
              Featured (maks. 3 aktif)
            </label>
            {errors.featured ? <p className="text-xs text-red-700">{errors.featured}</p> : null}
            <label className="flex items-center gap-2 text-sm font-semibold">
              <input
                type="checkbox"
                checked={data.published}
                onChange={(e) => setData("published", e.target.checked)}
              />
              Published
            </label>
          </div>

          <div className="flex items-center gap-4 pt-2">
            <button type="submit" disabled={processing} className="btn-primary disabled:opacity-60">
              {processing ? "Menyimpan…" : "Simpan"}
            </button>
            <Link href={route("admin.dashboard")} className="btn-outline">
              Batal
            </Link>
          </div>
        </form>
      </div>
    </>
  );
}

Form.layout = (page: ReactElement) => <AdminLayout children={page} />;

export default Form;

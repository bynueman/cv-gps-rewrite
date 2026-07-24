import type { FormEvent, ReactElement } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import AdminLayout from "@/Layouts/AdminLayout";
import { ImageUploadField, type UploadedImage } from "@/Components/admin/ImageUploadField";
import { useUnsavedChangesGuard } from "@/hooks/useUnsavedChangesGuard";

const inputClass =
  "w-full rounded-xl border border-espresso-900/15 bg-cream-50 px-4 py-3 text-sm text-espresso-900 outline-none transition-colors focus:border-gold-500 focus:ring-2 focus:ring-gold-400/30";
const labelClass = "mb-1.5 block text-sm font-semibold";

type PartnerFormValues = {
  id: number;
  name: string;
  category: string;
  logo: string | null;
  sort_order: number;
  is_active: boolean;
  featured: boolean;
};

function Form({ mode, partner }: { mode: "create" | "edit"; partner?: PartnerFormValues }) {
  const { data, setData, post, patch, processing, errors, isDirty } = useForm({
    name: partner?.name ?? "",
    category: partner?.category ?? "ritel",
    logo: partner?.logo ?? (null as string | null),
    sort_order: partner?.sort_order ?? 0,
    is_active: partner?.is_active ?? true,
    featured: partner?.featured ?? false,
  });

  const { bypassNext } = useUnsavedChangesGuard(isDirty);

  function handleLogoChange(value: UploadedImage) {
    setData("logo", value.image);
  }

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    bypassNext();
    if (mode === "create") {
      post(route("admin.partners.store"));
    } else {
      patch(route("admin.partners.update", partner!.id));
    }
  }

  return (
    <>
      <Head title={mode === "create" ? "Tambah Mitra" : "Edit Mitra"} />
      <h1 className="font-display text-2xl font-semibold">{mode === "create" ? "Tambah Mitra" : "Edit Mitra"}</h1>

      <form onSubmit={handleSubmit} className="mt-8 max-w-lg space-y-5">
        <div>
          <label className={labelClass}>Nama Mitra</label>
          <input type="text" value={data.name} onChange={(e) => setData("name", e.target.value)} className={inputClass} />
          {errors.name ? <p className="mt-1.5 text-sm text-red-700">{errors.name}</p> : null}
        </div>

        <div>
          <label className={labelClass}>Kategori</label>
          <select className={inputClass} value={data.category} onChange={(e) => setData("category", e.target.value)}>
            <option value="ritel">Ritel</option>
            <option value="hotel">Hotel</option>
            <option value="restaurant">Restoran</option>
            <option value="oleh-oleh">Oleh-Oleh</option>
            <option value="cakery">Cakery</option>
            <option value="institusi">Institusi</option>
          </select>
          {errors.category ? <p className="mt-1.5 text-sm text-red-700">{errors.category}</p> : null}
        </div>

        <div>
          <label className={labelClass}>Logo</label>
          <ImageUploadField
            initial={{ image: data.logo, imageThumb: null, imageOg: null }}
            onChange={handleLogoChange}
            context="partners"
            variant="logo"
          />
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
            Tampilkan di beranda
          </label>
        </div>
        <p className="-mt-3 text-xs text-espresso-500">
          Semua mitra aktif tetap tampil di halaman /mitra lengkap — centang ini hanya untuk memilih yang tampil
          di logo wall beranda.
        </p>

        <div className="flex items-center gap-4 pt-2">
          <button type="submit" disabled={processing} className="btn-primary disabled:opacity-60">
            {processing ? "Menyimpan…" : "Simpan"}
          </button>
          <Link href={route("admin.partners.index")} className="btn-outline">
            Batal
          </Link>
        </div>
      </form>
    </>
  );
}

Form.layout = (page: ReactElement<{ mode: "create" | "edit" }>) => (
  <AdminLayout title={page.props.mode === "create" ? "Tambah Mitra" : "Edit Mitra"} children={page} />
);

export default Form;

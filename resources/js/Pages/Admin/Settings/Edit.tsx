import { useState, type FormEvent, type ReactElement } from "react";
import { Head, useForm } from "@inertiajs/react";
import AdminLayout from "@/Layouts/AdminLayout";
import { ImageUploadField, type UploadedImage } from "@/Components/admin/ImageUploadField";
import { useUnsavedChangesGuard } from "@/hooks/useUnsavedChangesGuard";

const inputClass =
  "w-full rounded-xl border border-espresso-900/15 bg-cream-50 px-4 py-3 text-sm text-espresso-900 outline-none transition-colors focus:border-gold-500 focus:ring-2 focus:ring-gold-400/30";
const labelClass = "mb-1.5 block text-sm font-semibold";

type SettingsValues = {
  whatsapp: string;
  email_primary: string;
  email_secondary: string;
  address: string;
  instagram_kuicip: string;
  instagram_putriteko: string;
  operating_hours: string;
  site_title: string;
  meta_description_default: string;
  og_image_default: string;
};

function Edit({ settings }: { settings: SettingsValues }) {
  const [tab, setTab] = useState<"kontak" | "aplikasi">("kontak");

  const { data, setData, patch, processing, errors, isDirty } = useForm<SettingsValues>(settings);
  const { bypassNext } = useUnsavedChangesGuard(isDirty);

  function handleOgImageChange(value: UploadedImage) {
    // The og-crop variant (1200x630) is exactly what this field is for.
    setData("og_image_default", value.imageOg ?? value.image ?? "");
  }

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    bypassNext();
    patch(route("admin.settings.update"));
  }

  return (
    <>
      <Head title="Pengaturan" />
      <h1 className="font-display text-2xl font-semibold">Pengaturan</h1>

      <div className="mt-6 flex gap-2 border-b border-espresso-900/10">
        {(["kontak", "aplikasi"] as const).map((t) => (
          <button
            key={t}
            type="button"
            onClick={() => setTab(t)}
            className={`px-4 py-2 text-sm font-semibold ${
              tab === t ? "border-b-2 border-gold-600 text-espresso-950" : "text-espresso-500"
            }`}
          >
            {t === "kontak" ? "Kontak" : "Aplikasi"}
          </button>
        ))}
      </div>

      <form onSubmit={handleSubmit} className="mt-6 max-w-2xl space-y-5">
        <div className={tab === "kontak" ? "space-y-5" : "hidden"}>
          <div>
            <label className={labelClass}>Nomor WhatsApp</label>
            <input
              type="text"
              value={data.whatsapp}
              onChange={(e) => setData("whatsapp", e.target.value)}
              placeholder="+62 812-3456-7890"
              className={inputClass}
            />
            {errors.whatsapp ? <p className="mt-1.5 text-sm text-red-700">{errors.whatsapp}</p> : null}
          </div>

          <div className="grid gap-5 sm:grid-cols-2">
            <div>
              <label className={labelClass}>Email Utama</label>
              <input
                type="email"
                value={data.email_primary}
                onChange={(e) => setData("email_primary", e.target.value)}
                className={inputClass}
              />
              {errors.email_primary ? <p className="mt-1.5 text-sm text-red-700">{errors.email_primary}</p> : null}
            </div>
            <div>
              <label className={labelClass}>Email Kedua (opsional)</label>
              <input
                type="email"
                value={data.email_secondary}
                onChange={(e) => setData("email_secondary", e.target.value)}
                className={inputClass}
              />
            </div>
          </div>

          <div>
            <label className={labelClass}>Alamat</label>
            <textarea
              rows={3}
              value={data.address}
              onChange={(e) => setData("address", e.target.value)}
              className={inputClass}
            />
            {errors.address ? <p className="mt-1.5 text-sm text-red-700">{errors.address}</p> : null}
          </div>

          <div>
            <label className={labelClass}>Jam Operasional</label>
            <input
              type="text"
              value={data.operating_hours}
              onChange={(e) => setData("operating_hours", e.target.value)}
              placeholder="Senin–Sabtu, 08.00–16.00"
              className={inputClass}
            />
          </div>

          <div className="grid gap-5 sm:grid-cols-2">
            <div>
              <label className={labelClass}>Instagram Kuicip</label>
              <input
                type="text"
                value={data.instagram_kuicip}
                onChange={(e) => setData("instagram_kuicip", e.target.value)}
                placeholder="@kuicip.id"
                className={inputClass}
              />
            </div>
            <div>
              <label className={labelClass}>Instagram Putri Teko</label>
              <input
                type="text"
                value={data.instagram_putriteko}
                onChange={(e) => setData("instagram_putriteko", e.target.value)}
                placeholder="@putriteko.id"
                className={inputClass}
              />
            </div>
          </div>
        </div>

        <div className={tab === "aplikasi" ? "space-y-5" : "hidden"}>
          <div>
            <label className={labelClass}>Judul Situs</label>
            <input
              type="text"
              value={data.site_title}
              onChange={(e) => setData("site_title", e.target.value)}
              className={inputClass}
            />
            {errors.site_title ? <p className="mt-1.5 text-sm text-red-700">{errors.site_title}</p> : null}
          </div>

          <div>
            <label className={labelClass}>Meta Description Default</label>
            <textarea
              rows={3}
              maxLength={160}
              value={data.meta_description_default}
              onChange={(e) => setData("meta_description_default", e.target.value)}
              className={inputClass}
            />
            <p className="mt-1 text-xs text-espresso-500">{data.meta_description_default.length}/160 karakter</p>
            {errors.meta_description_default ? (
              <p className="mt-1.5 text-sm text-red-700">{errors.meta_description_default}</p>
            ) : null}
          </div>

          <div>
            <label className={labelClass}>Gambar OG Default (1200×630)</label>
            <ImageUploadField
              initial={{ image: data.og_image_default || null, imageThumb: null, imageOg: data.og_image_default || null }}
              onChange={handleOgImageChange}
              context="settings"
            />
          </div>
        </div>

        <div className="flex items-center gap-4 pt-2">
          <button type="submit" disabled={processing} className="btn-primary disabled:opacity-60">
            {processing ? "Menyimpan…" : "Simpan Pengaturan"}
          </button>
        </div>
      </form>
    </>
  );
}

Edit.layout = (page: ReactElement) => <AdminLayout title="Pengaturan" children={page} />;

export default Edit;

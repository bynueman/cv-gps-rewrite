import { TranslationPanel, type TranslationState } from "@/Components/admin/TranslationPanel";
import { useRef, useState, type FormEvent, type ReactElement } from "react";
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

function SettingsTranslation({state,dirty,onLoad,onRevisionChange}:{state?:TranslationState;dirty:boolean;onRevisionChange:(revision:string|null)=>void;onLoad:(values:Record<string,string>)=>void}) {
  const [revision,setRevision]=useState(state?.revision??null);
  return <TranslationPanel initial={state} dirty={dirty} revision={revision} onRevision={value=>{setRevision(value);onRevisionChange(value);}} onLoadEnglish={onLoad}/>;
}

function Edit({ settings, english = {}, translations = {} }: { settings: SettingsValues; english?:Record<string,string|null>; translations?:Record<string,TranslationState> }) {
  const [revisions,setRevisions]=useState<Record<string,string|null>>(Object.fromEntries(Object.entries(translations).map(([key,value])=>[key,value.revision])));
  const [tab, setTab] = useState<"kontak" | "aplikasi">("kontak");

  const { data, setData, patch, processing, errors, isDirty, setDefaults, transform } = useForm<SettingsValues & {english:Record<string,string>}>({...settings,english:Object.fromEntries(Object.entries(english).map(([key,value])=>[key,value??""]))});
  const englishSnapshot = useRef(data.english);
  const { bypassNext } = useUnsavedChangesGuard(isDirty);

  function handleOgImageChange(value: UploadedImage) {
    // The og-crop variant (1200x630) is exactly what this field is for.
    setData("og_image_default", value.imageOg ?? value.image ?? "");
  }

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    transform(values=>({...values,translation_revisions:revisions,translation_english_edits:Object.keys(values.english).filter(key=>values.english[key] !== (englishSnapshot.current[key]??""))}));
    bypassNext();
    patch(route("admin.settings.update"), {onSuccess:()=>{englishSnapshot.current=data.english;setDefaults();}});
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
        <details className="rounded-2xl border border-espresso-900/15 p-5"><summary className="cursor-pointer font-semibold">Terjemahan English — teks publik</summary><p className="mt-3 text-sm text-espresso-600">Jam operasional, judul situs dan deskripsi default ikut diproses saat Simpan. Alamat resmi, email, telepon, URL dan identitas tidak dikirim ke penerjemah.</p><div className="mt-5 space-y-5">{(["operating_hours","site_title","meta_description_default"] as const).map(key=><div key={key} className="space-y-3"><h2 className="text-sm font-semibold">{key.replace(/_/g," ")}</h2><SettingsTranslation state={translations[key]} dirty={isDirty} onRevisionChange={value=>setRevisions(prev=>({...prev,[key]:value}))} onLoad={values=>{const next={...data.english,[key]:values.value??""};englishSnapshot.current={...englishSnapshot.current,[key]:next[key]};setData("english",next);setDefaults({...data,english:next});}}/><label className={labelClass}>English · override manual<textarea className={inputClass} value={data.english[key]??""} onChange={e=>setData("english",{...data.english,[key]:e.target.value})} rows={2} maxLength={key==="meta_description_default"?160:255}/></label></div>)}</div></details>
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

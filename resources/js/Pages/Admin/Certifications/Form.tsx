import { useRef, useState, type FormEvent, type ReactElement } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import { Upload, Link2, Loader2, FileText, X } from "lucide-react";
import AdminLayout from "@/Layouts/AdminLayout";
import { ImageUploadField, type UploadedImage } from "@/Components/admin/ImageUploadField";
import { useUnsavedChangesGuard } from "@/hooks/useUnsavedChangesGuard";

const inputClass =
  "w-full rounded-xl border border-espresso-900/15 bg-cream-50 px-4 py-3 text-sm text-espresso-900 outline-none transition-colors focus:border-gold-500 focus:ring-2 focus:ring-gold-400/30";
const labelClass = "mb-1.5 block text-sm font-semibold";

const CATEGORIES = [
  { value: "legal", label: "Legalitas Usaha" },
  { value: "halal", label: "Halal" },
  { value: "bpom", label: "BPOM" },
  { value: "pirt", label: "P-IRT" },
  { value: "other", label: "Lainnya" },
];

type CertificationFormValues = {
  id: number;
  name: string;
  issuer: string | null;
  category: string;
  logo: string | null;
  pdf_url: string | null;
  valid_until: string | null;
  sort_order: number;
  is_active: boolean;
};

/** Detect whether a stored pdf_url is a server-upload or external URL */
function detectPdfMode(pdfUrl: string | null): "none" | "url" | "upload" {
  if (!pdfUrl) return "none";
  if (pdfUrl.startsWith("/uploads/")) return "upload";
  return "url";
}

function Form({ mode, certification }: { mode: "create" | "edit"; certification?: CertificationFormValues }) {
  const { data, setData, post, patch, processing, errors, isDirty } = useForm({
    name: certification?.name ?? "",
    issuer: certification?.issuer ?? "",
    category: certification?.category ?? "other",
    logo: certification?.logo ?? (null as string | null),
    pdf_url: certification?.pdf_url ?? (null as string | null),
    valid_until: certification?.valid_until ?? "",
    sort_order: certification?.sort_order ?? 0,
    is_active: certification?.is_active ?? true,
  });

  const { bypassNext } = useUnsavedChangesGuard(isDirty);

  // PDF mode state: "none" | "url" | "upload"
  const [pdfMode, setPdfMode] = useState<"none" | "url" | "upload">(
    detectPdfMode(certification?.pdf_url ?? null),
  );
  const [pdfUploading, setPdfUploading] = useState(false);
  const [pdfUploadError, setPdfUploadError] = useState<string | null>(null);
  const pdfInputRef = useRef<HTMLInputElement>(null);

  function handleLogoChange(value: UploadedImage) {
    setData("logo", value.image);
  }

  function handlePdfModeChange(mode: "none" | "url" | "upload") {
    setPdfMode(mode);
    setPdfUploadError(null);
    if (mode === "none") setData("pdf_url", null);
  }

  async function handlePdfFileChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    if (!file) return;

    setPdfUploading(true);
    setPdfUploadError(null);

    const formData = new FormData();
    formData.append("file", file);

    try {
      const res = await fetch(route("admin.upload-pdf"), {
        method: "POST",
        headers: {
          "X-CSRF-TOKEN": (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? "",
        },
        body: formData,
      });

      const json = await res.json();

      if (!res.ok || json.error) {
        setPdfUploadError(json.error ?? "Upload gagal.");
      } else {
        setData("pdf_url", json.url);
      }
    } catch {
      setPdfUploadError("Upload gagal. Periksa koneksi dan coba lagi.");
    } finally {
      setPdfUploading(false);
    }
  }

  function clearPdf() {
    setData("pdf_url", null);
    if (pdfInputRef.current) pdfInputRef.current.value = "";
  }

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    bypassNext();

    // Coerce empty strings to null for optional fields before sending
    setData((prev) => ({
      ...prev,
      issuer: prev.issuer || null,
      valid_until: prev.valid_until || null,
    }));

    if (mode === "create") {
      post(route("admin.certifications.store"));
    } else {
      patch(route("admin.certifications.update", certification!.id));
    }
  }

  const isUploadedPdf = data.pdf_url?.startsWith("/uploads/") ?? false;

  return (
    <>
      <Head title={mode === "create" ? "Tambah Sertifikasi" : "Edit Sertifikasi"} />
      <h1 className="font-display text-2xl font-semibold">
        {mode === "create" ? "Tambah Sertifikasi" : "Edit Sertifikasi"}
      </h1>

      <form onSubmit={handleSubmit} className="mt-8 max-w-lg space-y-5">
        {/* Name */}
        <div>
          <label className={labelClass}>Nama Sertifikasi / Dokumen</label>
          <input
            type="text"
            value={data.name}
            onChange={(e) => setData("name", e.target.value)}
            placeholder="contoh: Sertifikat Halal MUI, Izin BPOM, …"
            className={inputClass}
          />
          {errors.name ? <p className="mt-1.5 text-sm text-red-700">{errors.name}</p> : null}
        </div>

        {/* Issuer */}
        <div>
          <label className={labelClass}>
            Penerbit / Lembaga <span className="font-normal text-espresso-500">(opsional)</span>
          </label>
          <input
            type="text"
            value={data.issuer ?? ""}
            onChange={(e) => setData("issuer", e.target.value)}
            placeholder="contoh: MUI, BPOM RI, Dinas Kesehatan"
            className={inputClass}
          />
        </div>

        {/* Category */}
        <div>
          <label className={labelClass}>Kategori</label>
          <select
            className={inputClass}
            value={data.category}
            onChange={(e) => setData("category", e.target.value)}
          >
            {CATEGORIES.map((c) => (
              <option key={c.value} value={c.value}>
                {c.label}
              </option>
            ))}
          </select>
          {errors.category ? <p className="mt-1.5 text-sm text-red-700">{errors.category}</p> : null}
        </div>

        {/* Logo */}
        <div>
          <label className={labelClass}>Logo Sertifikasi</label>
          <p className="mb-2 text-xs text-espresso-500">
            Upload logo/badge sertifikasi. Akan dikonversi ke WebP dan ditampilkan di halaman publik.
          </p>
          <ImageUploadField
            initial={{ image: data.logo, imageThumb: null, imageOg: null }}
            onChange={handleLogoChange}
            context="certifications"
            variant="logo"
          />
        </div>

        {/* PDF Section */}
        <div className="space-y-3 rounded-xl border border-espresso-900/10 bg-white p-4">
          <p className="text-sm font-semibold text-espresso-900">Dokumen PDF</p>
          <p className="text-xs text-espresso-500">
            Opsional — tambahkan PDF jika visitor perlu melihat dokumen lengkap.
          </p>

          {/* Mode selector */}
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              onClick={() => handlePdfModeChange("none")}
              className={`rounded-lg px-3 py-2 text-xs font-semibold transition-colors ${
                pdfMode === "none"
                  ? "bg-espresso-900 text-cream-50"
                  : "border border-espresso-900/15 bg-cream-50 text-espresso-600 hover:border-espresso-900/30"
              }`}
            >
              Tidak ada PDF
            </button>
            <button
              type="button"
              onClick={() => handlePdfModeChange("url")}
              className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition-colors ${
                pdfMode === "url"
                  ? "bg-espresso-900 text-cream-50"
                  : "border border-espresso-900/15 bg-cream-50 text-espresso-600 hover:border-espresso-900/30"
              }`}
            >
              <Link2 className="h-3.5 w-3.5" />
              URL Eksternal
            </button>
            <button
              type="button"
              onClick={() => handlePdfModeChange("upload")}
              className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition-colors ${
                pdfMode === "upload"
                  ? "bg-espresso-900 text-cream-50"
                  : "border border-espresso-900/15 bg-cream-50 text-espresso-600 hover:border-espresso-900/30"
              }`}
            >
              <Upload className="h-3.5 w-3.5" />
              Upload File
            </button>
          </div>

          {/* URL mode */}
          {pdfMode === "url" ? (
            <div>
              <label className="mb-1 block text-xs font-semibold text-espresso-700">
                URL Dokumen
              </label>
              <input
                type="url"
                value={data.pdf_url ?? ""}
                onChange={(e) => setData("pdf_url", e.target.value || null)}
                placeholder="https://drive.google.com/..."
                className={inputClass}
              />
              <p className="mt-1 text-xs text-espresso-500">
                Google Drive, Dropbox, atau URL publik lainnya. Pastikan akses publik/dapat dilihat siapa saja.
              </p>
              {errors.pdf_url ? <p className="mt-1.5 text-sm text-red-700">{errors.pdf_url}</p> : null}
            </div>
          ) : null}

          {/* Upload mode */}
          {pdfMode === "upload" ? (
            <div>
              {data.pdf_url && isUploadedPdf ? (
                /* Already uploaded — show summary + option to replace */
                <div className="flex items-center gap-3 rounded-lg border border-espresso-900/10 bg-cream-50 p-3">
                  <FileText className="h-5 w-5 shrink-0 text-espresso-500" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-xs font-semibold text-espresso-900">
                      {data.pdf_url.split("/").pop()}
                    </p>
                    <a
                      href={data.pdf_url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-xs text-espresso-500 hover:underline"
                    >
                      Buka file
                    </a>
                  </div>
                  <button
                    type="button"
                    onClick={clearPdf}
                    className="shrink-0 rounded-lg p-1 text-espresso-400 hover:bg-cream-200 hover:text-espresso-900"
                    title="Hapus PDF"
                  >
                    <X className="h-4 w-4" />
                  </button>
                </div>
              ) : (
                /* File input */
                <div>
                  <label className="mb-1 block text-xs font-semibold text-espresso-700">
                    Pilih File PDF
                  </label>
                  <div className="flex items-center gap-3">
                    <label className="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-espresso-900/15 bg-cream-50 px-4 py-2.5 text-sm font-semibold text-espresso-900 transition-colors hover:border-espresso-900/30 hover:bg-cream-100">
                      {pdfUploading ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                      ) : (
                        <Upload className="h-4 w-4" />
                      )}
                      {pdfUploading ? "Mengupload…" : "Pilih PDF"}
                      <input
                        ref={pdfInputRef}
                        type="file"
                        accept="application/pdf,.pdf"
                        className="sr-only"
                        disabled={pdfUploading}
                        onChange={handlePdfFileChange}
                      />
                    </label>
                    <span className="text-xs text-espresso-500">Maks. 20 MB</span>
                  </div>
                  {pdfUploadError ? (
                    <p className="mt-1.5 text-sm text-red-700">{pdfUploadError}</p>
                  ) : null}
                </div>
              )}
            </div>
          ) : null}
        </div>

        {/* Valid until */}
        <div>
          <label className={labelClass}>
            Masa Berlaku <span className="font-normal text-espresso-500">(opsional)</span>
          </label>
          <input
            type="date"
            value={data.valid_until ?? ""}
            onChange={(e) => setData("valid_until", e.target.value || "")}
            className={`${inputClass} max-w-[14rem]`}
          />
          <p className="mt-1 text-xs text-espresso-500">
            Ditampilkan sebagai badge info di halaman publik.
          </p>
        </div>

        {/* Sort order */}
        <div>
          <label className={labelClass}>Urutan Tampil</label>
          <input
            type="number"
            value={data.sort_order}
            min={0}
            onChange={(e) => setData("sort_order", Number(e.target.value))}
            className={`${inputClass} max-w-[10rem]`}
          />
          <p className="mt-1 text-xs text-espresso-500">Angka lebih kecil tampil lebih awal.</p>
        </div>

        {/* Is active */}
        <div>
          <label className="flex items-center gap-2 text-sm font-semibold">
            <input
              type="checkbox"
              checked={data.is_active}
              onChange={(e) => setData("is_active", e.target.checked)}
            />
            Aktif (tampil di halaman publik)
          </label>
        </div>

        {/* Submit */}
        <div className="flex items-center gap-4 pt-2">
          <button type="submit" disabled={processing} className="btn-primary disabled:opacity-60">
            {processing ? "Menyimpan…" : "Simpan"}
          </button>
          <Link href={route("admin.certifications.index")} className="btn-outline">
            Batal
          </Link>
        </div>
      </form>
    </>
  );
}

Form.layout = (page: ReactElement<{ mode: "create" | "edit" }>) => (
  <AdminLayout title={page.props.mode === "create" ? "Tambah Sertifikasi" : "Edit Sertifikasi"} children={page} />
);

export default Form;

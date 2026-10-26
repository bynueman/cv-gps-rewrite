import { useEffect, useState } from "react";
import { AutoTranslateButton } from "@/Components/admin/AutoTranslateButton";
export type TranslationState = {
  type: string;
  id: number;
  revision: string | null;
  status: string;
  fields: Record<string, {
    status: string;
    mode: string;
    label?: string;
    reason?: string | null;
  }>;
};
export const translationLabels: Record<string, string> = {
  pending: "Menunggu terjemahan",
  processing: "Sedang diterjemahkan",
  ready: "Terjemahan siap",
  needs_review: "Perlu ditinjau",
  failed: "Terjemahan gagal",
  manual: "Diedit manual",
  stale: "Perlu diperbarui"
};
const reasons: Record<string, string> = {
  quota: "Batas penyedia tercapai; coba kembali setelah kuota tersedia",
  timeout: "Penyedia belum merespons",
  provider: "Penyedia gagal",
  unchanged: "Hasil belum diterjemahkan",
  protected_term: "Istilah merek perlu diperiksa",
  quantity: "Angka hasil berbeda",
  new_claim: "Klaim baru ditolak",
  queue_configuration: "Antrean perlu disiapkan operator",
  legacy_unverified: "English lama perlu ditinjau",
  claim_review: "Periksa klaim produk",
  concurrent_edit: "Konten berubah saat pemrosesan",
  worker: "Worker terhenti; terjemahkan ulang",
  processing: "Pemrosesan gagal",
  disabled: "Penyedia belum diaktifkan",
  target_length: "Hasil terlalu panjang",
  size: "Konten melebihi batas penerjemahan",
  storage: "Penyimpanan terjemahan belum tersedia"
};
export function TranslationPanel({
  initial,
  dirty,
  revision,
  onRevision,
  onLoadEnglish
}: {
  initial?: TranslationState;
  dirty: boolean;
  revision: string | null;
  onRevision: (value: string | null) => void;
  onLoadEnglish: (values: Record<string, string>) => void;
}) {
  const [state, setState] = useState(initial);
  const [error, setError] = useState("");
  useEffect(() => {
    setState(initial);
    onRevision(initial?.revision ?? null);
  }, [initial?.id, initial?.revision]);
  async function read(loadEnglish = false) {
    if (!state) return;
    try {
      const r = await window.axios.get(route("admin.translations.status"), {
        params: {
          type: state.type,
          id: state.id
        }
      });
      if (r.data.revision !== revision) {
        setError("Konten berubah di server. Muat ulang halaman sebelum melanjutkan agar edit manual tidak tertimpa.");
        return;
      }
      setState(r.data);
      setError("");
      if (loadEnglish) onLoadEnglish(r.data.english);
    } catch {
      setError("Status belum dapat dimuat. Konten Indonesia tetap tersimpan.");
    }
  }
  useEffect(() => {
    if (!state || !["pending", "processing"].includes(state.status)) return;
    const timer = window.setInterval(() => void read(), 5000);
    return () => window.clearInterval(timer);
  }, [state?.type, state?.id, state?.status, revision]);
  async function retry(overwrite = false) {
    if (!state || dirty) return;
    if (overwrite && !window.confirm("Ganti juga English yang pernah diedit manual dengan terjemahan baru? Salinan English manual sebelumnya tetap disimpan untuk peninjauan.")) return;
    const r = await window.axios.post(route("admin.translations.retry"), {
      type: state.type,
      id: state.id,
      revision,
      overwrite_manual: overwrite
    });
    setState(r.data);
    onRevision(r.data.revision);
    setError("");
  }
  return <section className="rounded-2xl border border-espresso-900/15 bg-cream-100 p-5">
    <div className="flex flex-wrap items-center justify-between gap-3"><h2 className="font-semibold">Konten Indonesia → English</h2><p role="status" className="rounded-full bg-cream-50 px-3 py-1 text-xs font-semibold">{state ? translationLabels[state.status] || state.status : "Belum diterjemahkan"}</p></div>
    <p className="mt-3 text-sm leading-relaxed text-espresso-700">Tulis Indonesia dan Simpan sekali. English diproses di antrean, tanpa menghapus sumber. Edit English manual dilindungi; perubahan Indonesia dapat memerlukan peninjauan. Hanya masukkan konten perusahaan yang boleh dikirim ke penyedia terjemahan.</p>
    {state && <><div className="mt-4 flex flex-wrap items-start gap-3"><button type="button" className="btn-outline" onClick={() => void read()}>Periksa status</button><button type="button" disabled={dirty} className="btn-outline disabled:opacity-50" onClick={() => void read(true)}>Pratinjau English tersimpan</button><AutoTranslateButton disabled={dirty} onTranslate={() => retry()} /></div>
      <details className="mt-4 text-sm"><summary className="cursor-pointer font-semibold">Rincian & penggantian manual</summary><ul className="mt-3 space-y-2">{Object.entries(state.fields).map(([field, value]) => <li key={field} className="flex flex-wrap justify-between gap-2"><span>{value.label || field}</span><span>{translationLabels[value.status] || value.status}{value.reason ? ` · ${reasons[value.reason] || "Perlu peninjauan"}` : ""}</span></li>)}</ul><div className="mt-4"><AutoTranslateButton disabled={dirty} label="Terjemahkan ulang termasuk edit manual…" onTranslate={() => retry(true)} /></div></details>
      <p className="mt-3 text-xs leading-relaxed text-espresso-600">{dirty ? "Simpan perubahan formulir sebelum memuat atau mengganti English." : "Antrean memerlukan worker terjadwal. Jika gagal, sumber tetap tersedia dan English tidak dianggap selesai."}</p></>}
    {error && <p role="alert" className="mt-3 text-sm text-red-700">{error}</p>}
  </section>;
}

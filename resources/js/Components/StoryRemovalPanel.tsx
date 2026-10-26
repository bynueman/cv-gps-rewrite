import { useState, type FormEvent } from "react";
import { ChevronDown, KeyRound, LoaderCircle } from "lucide-react";
import { useLang } from "@/lib/i18n";

export function StoryRemovalPanel({ onRemoved }: { onRemoved: () => void }) {
  const { lang } = useLang();
  const id = lang === "id";
  const [busy, setBusy] = useState(false);
  const [feedback, setFeedback] = useState<{ error: boolean; text: string } | null>(null);
  async function remove(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;
    const form = new FormData(event.currentTarget);
    setBusy(true); setFeedback(null);
    try {
      await window.axios.post(route("stories.removal"), { code: form.get("code"), token: form.get("token") });
      setFeedback({ error: false, text: id ? "Permintaan diterima. Jika kode dan token cocok, cerita dihapus dari ruang publik." : "Request received. A matching story and token removes the story from public view." });
      onRemoved();
    } catch {
      setFeedback({ error: true, text: id ? "Permintaan belum dapat diproses. Periksa format kode dan token, lalu coba lagi." : "The request could not be processed. Check the code and token format, then try again." });
    } finally { setBusy(false); }
  }
  const field = "mt-2 w-full rounded-xl border border-espresso-900/20 bg-cream-50 px-4 py-3 text-base";
  return <details className="group mt-10 rounded-2xl border border-espresso-900/15 bg-cream-50">
    <summary className="flex cursor-pointer list-none items-center gap-3 rounded-2xl p-5 sm:px-7">
      <KeyRound size={20} className="shrink-0 text-espresso-600" aria-hidden="true" />
      <span className="flex-1"><span className="block text-sm font-semibold">{id ? "Ingin menghapus ceritamu?" : "Want to remove your story?"}</span><span className="mt-1 block text-xs leading-relaxed text-espresso-600">{id ? "Gunakan kode cerita dan token pribadi yang kamu simpan." : "Use your story code and the private token you saved."}</span></span>
      <ChevronDown size={18} className="shrink-0 transition-transform group-open:rotate-180 motion-reduce:transition-none" aria-hidden="true" />
    </summary>
    <div className="border-t border-espresso-900/10 p-5 sm:p-7">
      <p className="max-w-2xl text-sm leading-relaxed text-espresso-700">{id ? "Tempel token penghapusan untuk kirimanmu sendiri. Token harus dipasangkan dengan kode cerita yang sesuai; kode saja tidak cukup. Jangan membagikan token ini kepada orang lain." : "Paste the removal token for your own submission. It must match its story code; the code alone is not enough. Keep the token private."}</p>
      <form onSubmit={remove} className="mt-5 grid items-start gap-4 sm:grid-cols-[1fr_2fr]">
        <label className="text-sm font-semibold">{id ? "Kode cerita" : "Story code"}<input required name="code" placeholder="KC-0006" pattern="KC-[0-9]{4,20}" className={field} /></label>
        <label className="text-sm font-semibold">{id ? "Token penghapusan" : "Removal token"}<input required name="token" type="password" autoComplete="off" pattern="[a-f0-9]{64}" maxLength={64} className={field} /></label>
        <div className="sm:col-span-2"><button disabled={busy} className="btn-outline min-h-12 disabled:opacity-50">{busy && <LoaderCircle size={16} className="motion-safe:animate-spin" aria-hidden="true" />}{busy ? (id ? "Memproses…" : "Processing…") : (id ? "Ajukan penghapusan" : "Request removal")}</button></div>
      </form>
      {feedback && <p role={feedback.error ? "alert" : "status"} className={`mt-5 rounded-xl border p-4 text-sm leading-relaxed ${feedback.error ? "border-red-200 bg-red-50 text-red-800" : "border-herbal/25 bg-herbal/5 text-espresso-800"}`}>{feedback.text}</p>}
    </div>
  </details>;
}

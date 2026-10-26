import { storyCategoryName } from "@/lib/storyLabels";
import { useState, type FormEvent } from "react";
import { Heart, Flag, ArrowUpRight } from "lucide-react";
import { useLang, type Lang } from "@/lib/i18n";
import type { PublicStory } from "@/types";
import { StoryDetailModal, StoryText } from "@/Components/StoryDetailModal";

export function StoryActions({
  code, supports, busy, message, lang, onSupport, onReport,
}: {
  code: string;
  supports: number;
  busy: boolean;
  message: string;
  lang: Lang;
  onSupport: () => void;
  onReport: (event: FormEvent<HTMLFormElement>) => void;
}) {
  const id = lang === "id";
  return (
    <>
      <div className="flex items-center justify-between gap-3">
        <button
          type="button"
          disabled={busy}
          onClick={onSupport}
          aria-label={(id ? "Dukung cerita " : "Support story ") + code}
          className="inline-flex min-h-11 items-center gap-2 rounded-full border border-espresso-900/10 px-3 py-2 text-sm font-semibold transition-colors hover:bg-gold-300/20 disabled:opacity-50"
        >
          <Heart size={18} aria-hidden="true" />{supports} <span>{id ? "Dukung" : "Support"}</span>
        </button>
        <details className="relative">
          <summary className="flex min-h-11 min-w-11 cursor-pointer list-none items-center justify-center rounded-full border border-transparent p-2 text-sm hover:border-espresso-900/15 hover:bg-cream-100">
            <span className="sr-only">{id ? "Laporkan cerita " : "Report story "}{code}</span>
            <Flag size={18} aria-hidden="true" />
          </summary>
          <form onSubmit={onReport} className="absolute bottom-12 right-0 z-10 w-52 rounded-2xl border border-espresso-900/15 bg-cream-50 p-4 shadow-card">
            <label className="text-sm">
              {id ? "Alasan laporan" : "Report reason"}
              <select name="reason" className="mt-2 w-full rounded-xl border border-espresso-900/20 bg-cream-50 px-3 py-2 text-sm">
                <option value="privacy">{id ? "Data pribadi" : "Privacy"}</option>
                <option value="harmful">{id ? "Konten berbahaya" : "Harmful content"}</option>
                <option value="spam">Spam</option>
                <option value="other">{id ? "Lainnya" : "Other"}</option>
              </select>
            </label>
            <button disabled={busy} className="btn-dark mt-3 w-full disabled:opacity-50">{id ? "Kirim laporan" : "Send report"}</button>
          </form>
        </details>
      </div>
      <p role="status" className="mt-3 min-h-5 text-xs leading-5 text-espresso-600">{message}</p>
    </>
  );
}

export function StoryCard({ story }: { story: PublicStory }) {
  const { lang } = useLang();
  const id = lang === "id";
  const [open, setOpen] = useState(false);
  const [supports, setSupports] = useState(story.supports);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");

  async function react() {
    if (busy) return;
    setBusy(true); setMessage("");
    try {
      const response = await window.axios.post(route("stories.react", story.id));
      setSupports(response.data.supports);
      setMessage(id ? "Dukungan diterima." : "Support received.");
    } catch {
      setMessage(id ? "Belum dapat diproses. Coba lagi nanti." : "Please try again later.");
    } finally { setBusy(false); }
  }

  async function report(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;
    const form = new FormData(event.currentTarget);
    setBusy(true); setMessage("");
    try {
      await window.axios.post(route("stories.report", story.id), { reason: form.get("reason") });
      setMessage(id ? "Laporan diterima untuk ditinjau." : "Report received for review.");
    } catch {
      setMessage(id ? "Laporan belum dapat dikirim. Coba lagi nanti." : "Please try again later.");
    } finally { setBusy(false); }
  }

  // Both views render the same controlled actions; opening the dialog never fetches or reacts.
  const actions = <StoryActions code={story.code} supports={supports} busy={busy} message={message} lang={lang} onSupport={react} onReport={report} />;

  return (
    <>
      <article className="flex h-full min-w-0 flex-col rounded-3xl border border-espresso-900/10 bg-cream-50 p-5 transition-colors hover:border-gold-700/40 sm:p-6">
        <div className="flex items-center justify-between gap-3">
          <span className="rounded-full bg-gold-300/40 px-3 py-1 text-xs font-semibold capitalize">{storyCategoryName(story.category, lang)}</span>
          <span className="shrink-0 font-mono text-xs text-espresso-600">{story.code}</span>
        </div>
        <div className="my-5"><StoryText body={story.body} preview /></div>
        <button
          type="button"
          onClick={(event) => { event.currentTarget.focus(); setOpen(true); }}
          aria-haspopup="dialog"
          aria-expanded={open}
          aria-label={(id ? "Baca selengkapnya cerita " : "Read full story ") + story.code}
          className="inline-flex min-h-11 items-center gap-2 self-start rounded-lg text-sm font-semibold text-espresso-700 underline decoration-gold-700/60 underline-offset-4 hover:text-espresso-950"
        >
          {id ? "Baca selengkapnya" : "Read full story"}<ArrowUpRight size={16} aria-hidden="true" />
        </button>
        <div className="mt-auto pt-4"><div className="border-t border-espresso-900/10 pt-4">{actions}</div></div>
      </article>
      <StoryDetailModal story={story} open={open} onClose={() => setOpen(false)} lang={lang}>{actions}</StoryDetailModal>
    </>
  );
}

import { useEffect, useRef } from "react";
import { CheckCircle2 } from "lucide-react";
import { useLang } from "@/lib/i18n";
import { CopyStoryCode } from "@/Components/CopyStoryCode";

export type StoryReceiptData = { code: string; accepted?: boolean };

export function StoryReceipt({ receipt, contactEmail }: { receipt: StoryReceiptData; contactEmail: string }) {
  const { lang } = useLang();
  const id = lang === "id";
  const heading = useRef<HTMLHeadingElement>(null);
  useEffect(() => { heading.current?.focus(); }, [receipt.code]);
  return <section aria-labelledby="story-receipt-title" className="mb-7 rounded-2xl border border-herbal/30 bg-herbal/5 p-5 sm:p-6">
    <div role="status" className="flex items-start gap-3">
      <CheckCircle2 size={22} className="mt-0.5 shrink-0 text-herbal" aria-hidden="true" />
      <div>
        <h3 ref={heading} tabIndex={-1} id="story-receipt-title" className="scroll-mt-28 font-display text-xl">{id ? "Ceritamu sudah diterima." : "Your story has been received."}</h3>
        <p className="mt-2 text-sm leading-relaxed text-espresso-700">{id
          ? "Ceritamu menunggu peninjauan. Cerita publik hanya tampil setelah persetujuan admin; cerita privat tidak dipublikasikan."
          : "Your story is awaiting review. Public stories appear only after admin approval; private stories are never published."}</p>
      </div>
    </div>
    <div className="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-espresso-900/10 bg-cream-50 p-4">
      <div><p className="text-xs text-espresso-600">{id ? "Kode ceritamu" : "Your story code"}</p><p className="mt-1 select-all font-mono text-lg font-semibold">{receipt.code}</p></div>
      <CopyStoryCode code={receipt.code} lang={lang} />
    </div>
    <p className="mt-4 text-sm leading-relaxed text-espresso-700">{id ? "Permintaan penghapusan: sertakan kode cerita dan hubungi " : "For removal requests, include your story code and contact "}<a href={`mailto:${contactEmail}`} className="break-words font-semibold underline underline-offset-2">{id ? "kontak resmi GPS FOOD" : "the official GPS FOOD contact"}</a>.</p>
  </section>;
}

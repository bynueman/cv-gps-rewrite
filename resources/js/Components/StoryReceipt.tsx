import { useEffect, useRef } from "react";
import { CheckCircle2 } from "lucide-react";
import { useLang } from "@/lib/i18n";
import { CopyStoryCode } from "@/Components/CopyStoryCode";
import { StoryRemovalHelp } from "@/Components/StoryRemovalHelp";

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
          ? "Ceritamu sudah diterima dan menunggu peninjauan. Simpan kode cerita ini sebagai referensi. Kiriman tidak langsung tampil di ruang publik."
          : "Your story has been received and is awaiting review. Keep this story code for reference. Submissions do not appear publicly right away."}</p>
      </div>
    </div>
    <div className="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-espresso-900/10 bg-cream-50 p-4">
      <div><p className="text-xs text-espresso-600">{id ? "Kode ceritamu" : "Your story code"}</p><p className="mt-1 select-all font-mono text-lg font-semibold">{receipt.code}</p></div>
      <CopyStoryCode code={receipt.code} lang={lang} />
    </div>
    <div className="mt-4"><StoryRemovalHelp email={contactEmail}/></div>
  </section>;
}

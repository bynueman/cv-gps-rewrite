import { useEffect, useRef } from "react";
import { CheckCircle2, KeyRound } from "lucide-react";
import { useLang } from "@/lib/i18n";
import { CopyStoryCode } from "@/Components/CopyStoryCode";

export type StoryReceiptData = { code: string; deletion_token?: string };

export function StoryReceipt({ receipt }: { receipt: StoryReceiptData }) {
  const { lang } = useLang();
  const id = lang === "id";
  const heading = useRef<HTMLHeadingElement>(null);
  useEffect(() => { heading.current?.focus(); }, [receipt.code]);
  return <section aria-labelledby="story-receipt-title" className="mb-7 rounded-2xl border border-herbal/30 bg-herbal/5 p-5 sm:p-6">
    <div role="status" className="flex items-start gap-3">
      <CheckCircle2 size={22} className="mt-0.5 shrink-0 text-herbal" aria-hidden="true" />
      <div>
        <h3 ref={heading} tabIndex={-1} id="story-receipt-title" className="scroll-mt-28 font-display text-xl">{id ? "Ceritamu sudah diterima." : "Your story has been received."}</h3>
        <p className="mt-2 text-sm leading-relaxed text-espresso-700">{id ? "Terima kasih sudah berbagi. Tim akan meninjau ceritamu; kiriman tidak langsung tampil di ruang publik." : "Thank you for sharing. Our team will review your story; submissions do not appear publicly right away."}</p>
      </div>
    </div>
    <div className="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-espresso-900/10 bg-cream-50 p-4">
      <div><p className="text-xs text-espresso-600">{id ? "Kode ceritamu" : "Your story code"}</p><p className="mt-1 select-all font-mono text-lg font-semibold">{receipt.code}</p></div>
      <CopyStoryCode code={receipt.code} lang={lang} />
    </div>
    {receipt.deletion_token ? <div className="mt-5">
      <label htmlFor="story-removal-receipt" className="flex items-center gap-2 text-sm font-semibold"><KeyRound size={16} aria-hidden="true" />{id ? "Simpan token penghapusan pribadi" : "Save your private removal token"}</label>
      <textarea id="story-removal-receipt" aria-label="Removal token" readOnly rows={3} value={receipt.deletion_token} autoComplete="off"
        onFocus={(event) => event.currentTarget.select()} className="mt-3 w-full resize-none rounded-xl border border-espresso-900/20 bg-cream-50 p-3 font-mono text-sm leading-6 [overflow-wrap:anywhere]" />
      <p className="mt-2 text-xs leading-relaxed text-espresso-700">{id ? "Token ini hanya ditampilkan sekali. Salin dan simpan di tempat pribadi sebelum meninggalkan halaman. Kode cerita saja tidak cukup untuk menghapus kiriman." : "This token is shown only once. Copy and store it privately before leaving this page. The story code alone cannot remove a submission."}</p>
    </div> : <p className="mt-4 text-sm leading-relaxed text-espresso-700">{id ? "Kiriman ini sudah pernah diterima. Token penghapusan tidak dapat ditampilkan ulang; gunakan salinan yang sebelumnya kamu simpan." : "This submission was already received. The removal token cannot be shown again; use the copy you previously saved."}</p>}
  </section>;
}

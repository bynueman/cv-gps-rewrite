import { useState } from "react";
import { Check, Copy } from "lucide-react";
import type { Lang } from "@/lib/i18n";

export function CopyStoryCode({ code, lang = "id" }: { code: string; lang?: Lang }) {
  const [copiedCode, setCopiedCode] = useState("");
  const [failedCode, setFailedCode] = useState("");
  const id = lang === "id";
  const copied = copiedCode === code;
  async function copy() {
    try {
      await navigator.clipboard.writeText(code);
      setCopiedCode(code); setFailedCode("");
    } catch { setFailedCode(code); }
  }
  return <div className="inline-flex flex-col items-start gap-1">
    <button type="button" onClick={copy} aria-label={(id ? "Salin kode cerita " : "Copy story code ") + code}
      className="inline-flex min-h-10 items-center gap-2 rounded-full border border-espresso-900/20 px-3 py-2 text-xs font-semibold text-espresso-700 transition-colors hover:bg-cream-200">
      {copied ? <Check size={14} aria-hidden="true" /> : <Copy size={14} aria-hidden="true" />}
      <span role="status">{copied ? (id ? "Kode tersalin" : "Code copied") : (id ? "Salin kode" : "Copy code")}</span>
    </button>
    {failedCode === code && <span role="status" className="text-xs text-espresso-600">{id ? "Pilih dan salin kode secara manual." : "Select and copy the code manually."}</span>}
  </div>;
}

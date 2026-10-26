import { useState } from "react";
import { Languages, Loader2 } from "lucide-react";
export function AutoTranslateButton({
  onTranslate,
  disabled = false,
  label = "Terjemahkan ulang"
}: {
  onTranslate: () => Promise<void>;
  disabled?: boolean;
  label?: string;
}) {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  async function run() {
    setBusy(true);
    setError("");
    try {
      await onTranslate();
    } catch {
      setError("Antrean belum dapat diperbarui. Periksa status lalu coba lagi.");
    } finally {
      setBusy(false);
    }
  }
  return <div><button type="button" disabled={busy || disabled} onClick={() => void run()} className="btn-outline min-h-11 disabled:opacity-50">{busy ? <Loader2 size={16} className="motion-safe:animate-spin" aria-hidden="true" /> : <Languages size={16} aria-hidden="true" />}{busy ? "Menyiapkan antrean…" : label}</button>{error && <p role="alert" className="mt-2 text-xs text-red-700">{error}</p>}</div>;
}

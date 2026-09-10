import { useState } from "react";
import { Languages, Loader2, Check } from "lucide-react";

type Props = {
  sourceLang: "id" | "en";
  targetLang: "id" | "en";
  getFields: () => Record<string, string | null | undefined>;
  onSuccess: (translations: Record<string, string>) => void;
  className?: string;
};

export function AutoTranslateButton({
  sourceLang,
  targetLang,
  getFields,
  onSuccess,
  className = "",
}: Props) {
  const [translating, setTranslating] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const targetLabel = targetLang === "en" ? "English" : "Indonesia";

  async function handleTranslate() {
    const fields = getFields();
    const hasContent = Object.values(fields).some((v) => v && v.trim() !== "");

    if (!hasContent) {
      setError(`Isi data pada tab ${sourceLang === "id" ? "Indonesia" : "English"} terlebih dahulu.`);
      setTimeout(() => setError(null), 3000);
      return;
    }

    setTranslating(true);
    setError(null);
    setSuccess(false);

    try {
      const response = await window.axios.post(route("admin.translate"), {
        from: sourceLang,
        to: targetLang,
        fields,
      });

      if (response.data?.translations) {
        onSuccess(response.data.translations);
        setSuccess(true);
        setTimeout(() => setSuccess(false), 2500);
      }
    } catch (err: any) {
      setError(err?.response?.data?.error || "Gagal menerjemahkan. Coba lagi.");
      setTimeout(() => setError(null), 3500);
    } finally {
      setTranslating(false);
    }
  }

  return (
    <div className={`flex items-center gap-2 ${className}`}>
      <button
        type="button"
        onClick={handleTranslate}
        disabled={translating}
        title={`Terjemahkan seluruh teks dari ${sourceLang === "id" ? "Indonesia" : "English"} ke ${targetLabel}`}
        className="inline-flex items-center gap-1.5 rounded-lg border border-gold-400/40 bg-gold-50/80 px-3 py-1.5 text-xs font-semibold text-espresso-900 transition-all hover:border-gold-500 hover:bg-gold-100/90 active:scale-95 disabled:opacity-50 disabled:pointer-events-none"
      >
        {translating ? (
          <Loader2 className="h-3.5 w-3.5 animate-spin text-gold-700" />
        ) : success ? (
          <Check className="h-3.5 w-3.5 text-green-700" />
        ) : (
          <Languages className="h-3.5 w-3.5 text-gold-700" />
        )}
        <span>
          {translating
            ? "Menerjemahkan…"
            : success
            ? "Berhasil Diterjemahkan!"
            : `Terjemahkan ke ${targetLabel}`}
        </span>
      </button>

      {error ? (
        <span className="text-xs text-red-600 animate-fadeIn">{error}</span>
      ) : null}
    </div>
  );
}

import { useLang, type Lang } from "@/lib/i18n";

export function LangSwitch({ tone = "light" }: { tone?: "light" | "dark" }) {
  const { lang, setLang } = useLang();
  const options: Lang[] = ["id", "en"];

  return (
    <div
      role="group"
      aria-label="Language"
      className={`inline-flex items-center rounded-full border p-0.5 text-xs font-semibold ${
        tone === "light" ? "border-espresso-900/20" : "border-cream-100/25"
      }`}
    >
      {options.map((code) => {
        const active = lang === code;
        return (
          <button
            key={code}
            type="button"
            onClick={() => setLang(code)}
            aria-pressed={active}
            className={`rounded-full px-2.5 py-1 uppercase transition-colors ${
              active
                ? "bg-gold-500 text-espresso-950"
                : tone === "light"
                  ? "text-espresso-600 hover:text-espresso-900"
                  : "text-cream-200/70 hover:text-cream-100"
            }`}
          >
            {code}
          </button>
        );
      })}
    </div>
  );
}

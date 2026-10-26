import type { Lang } from "@/lib/i18n";
import type { Bilingual } from "@/types";
type Value = {
  id?: string | null;
  en?: string | null;
  en_stale?: boolean;
};
export function localeValue(value: Value | null | undefined, lang: Lang) {
  const id = typeof value?.id === "string" ? value.id.trim() : "";
  const en = typeof value?.en === "string" ? value.en.trim() : "";
  const stale = value?.en_stale || /no\s*msg/i.test(id) && !/no\s*msg/i.test(en);
  const selected = lang === "en" && !stale ? en : lang === "id" ? id : "";
  const other = lang === "en" ? id : en;
  return {
    text: selected || other,
    language: (selected ? lang : lang === "en" ? "id" : "en") as Lang,
    fallback: !selected && !!other
  };
}
export function localized(value: Value | null | undefined, lang: Lang, empty = "") {
  return localeValue(value, lang).text || empty;
}
export function LocalizedText({
  value,
  lang,
  empty = ""
}: {
  value: Value | null | undefined;
  lang: Lang;
  empty?: string;
}) {
  const result = localeValue(value, lang);
  if (!result.text) return <>{empty}</>;
  return <span lang={result.language}>{result.text}{result.fallback && <small className="ml-1 inline-block text-[11px] font-normal tracking-normal" title={lang === "en" ? "Original Indonesian text; English is not ready." : "Teks asli English; Indonesia belum tersedia."}>[{result.language.toUpperCase()}]</small>}</span>;
}
export function LocalizedList({
  items,
  lang,
  color
}: {
  items: Bilingual[] | null | undefined;
  lang: Lang;
  color: string;
}) {
  const visible = (items ?? []).filter(item => localeValue(item, lang).text);
  if (!visible.length) return <p className="mt-3 text-sm text-espresso-600">{lang === "id" ? "Belum ada rincian tambahan." : "No additional details yet."}</p>;
  return <ul className="mt-3 space-y-2.5">{visible.map((item, i) => <li key={item.key || i} className="flex items-start gap-2.5 text-sm leading-relaxed text-espresso-700"><span className="mt-1.5 h-2 w-2 shrink-0 rounded-full" style={{
        backgroundColor: color
      }} aria-hidden="true" /><LocalizedText value={item} lang={lang} /></li>)}</ul>;
}

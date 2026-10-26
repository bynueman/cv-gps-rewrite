export type BilingualItem = {
  key: string;
  id: string;
  en: string;
};
export function BilingualListEditor({
  label,
  items,
  onChange,
  errors = {}
}: {
  label: string;
  items: BilingualItem[];
  onChange: (items: BilingualItem[]) => void;
  errors?: Record<string, string>;
}) {
  function change(key: string, lang: "id" | "en", value: string) {
    onChange(items.map(item => item.key === key ? {
      ...item,
      [lang]: value
    } : item));
  }
  function move(index: number, direction: number) {
    const next = [...items];
    [next[index], next[index + direction]] = [next[index + direction], next[index]];
    onChange(next);
  }
  return <fieldset className="rounded-2xl border border-espresso-900/15 p-5"><legend className="px-2 text-sm font-semibold">{label}</legend><p className="text-xs leading-relaxed text-espresso-600">Satu poin per item. English mengikuti identitas item saat diurutkan; tidak dipasangkan ulang berdasarkan nomor baris.</p>
    <div className="mt-4 space-y-4">{items.map((item, index) => <div key={item.key} className="rounded-xl border border-espresso-900/10 bg-cream-100 p-4">
      <label className="block text-sm font-semibold">Poin {index + 1} — Indonesia<textarea required rows={2} maxLength={2000} value={item.id} onChange={e => change(item.key, "id", e.target.value)} className="mt-2 w-full rounded-xl border border-espresso-900/20 bg-cream-50 p-3 font-normal" /></label>
      <details className="mt-3 text-sm"><summary className="cursor-pointer text-espresso-600">Terjemahan English · edit manual</summary><label className="mt-2 block">English<textarea rows={2} maxLength={2000} value={item.en} onChange={e => change(item.key, "en", e.target.value)} className="mt-2 w-full rounded-xl border border-espresso-900/20 bg-cream-50 p-3" /></label></details>
      <div className="mt-3 flex flex-wrap gap-2"><button type="button" disabled={index === 0} aria-label={`Naikkan poin ${index + 1}`} className="min-h-11 rounded-lg border border-espresso-900/20 px-3 text-xs disabled:opacity-30" onClick={() => move(index, -1)}>↑ Naik</button><button type="button" disabled={index === items.length - 1} aria-label={`Turunkan poin ${index + 1}`} className="min-h-11 rounded-lg border border-espresso-900/20 px-3 text-xs disabled:opacity-30" onClick={() => move(index, 1)}>↓ Turun</button><button type="button" className="min-h-11 rounded-lg px-3 text-xs text-red-700" onClick={() => onChange(items.filter(row => row.key !== item.key))}>Hapus poin {index + 1}</button></div>
    </div>)}</div>
    <button type="button" disabled={items.length >= 50} className="btn-outline mt-4 disabled:opacity-50" onClick={() => onChange([...items, {
      key: crypto.randomUUID(),
      id: "",
      en: ""
    }])}>Tambah poin</button>
    {Object.entries(errors).map(([key, message]) => <p key={key} role="alert" className="mt-2 text-sm text-red-700">{message}</p>)}
  </fieldset>;
}

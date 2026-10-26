import { localized } from "@/lib/localized";
import { useMemo, useState } from "react";
import { Search } from "lucide-react";
import { useLang } from "@/lib/i18n";
import { ProductCard } from "@/Components/ProductCard";
import type { Product } from "@/types";

export function ProductCatalog({ products }: { products: Product[]; brand: "kuicip" | "putri-teko" }) {
  const { lang, t } = useLang();
  const [query, setQuery] = useState("");
  const [packaging, setPackaging] = useState("");
  const [group, setGroup] = useState("");
  const packs = [...new Set(products.flatMap(p => p.packaging ? [p.packaging] : []))];
  const groups = [...new Set(products.flatMap(p => p.group ? [p.group] : []))];
  const filtered = useMemo(() => products.filter(p =>
    localized(p.name, lang).toLocaleLowerCase().includes(query.trim().toLocaleLowerCase())
    && (!packaging || p.packaging === packaging) && (!group || p.group === group)), [products, lang, query, packaging, group]);
  const id = lang === "id";
  return <section className="pb-24">
    <div className="container-page">
      <div className="mb-10 flex flex-col gap-5 border-y border-espresso-900/15 py-6 md:flex-row md:items-end md:justify-between">
        <div><p className="kicker">{id ? "Koleksi pilihan" : "Explore the collection"}</p><p className="mt-2 text-sm text-espresso-600" aria-live="polite">{filtered.length} {id ? "produk" : "products"}</p></div>
        <div className="flex flex-wrap gap-3">
          <label className="relative flex min-w-0 flex-1 items-center gap-2 rounded-full border border-espresso-900/20 bg-cream-50 px-4"><Search size={18} aria-hidden="true"/><span className="sr-only">{id ? "Cari produk" : "Search products"}</span><input type="search" value={query} onChange={e => setQuery(e.target.value)} placeholder={id ? "Cari nama produk…" : "Search by name…"} className="w-full min-w-40 border-0 bg-transparent py-3 text-sm outline-none"/></label>
          {packs.length > 0 && <label><span className="sr-only">{id ? "Kemasan" : "Packaging"}</span><select value={packaging} onChange={e => setPackaging(e.target.value)} className="catalog-select"><option value="">{id ? "Semua kemasan" : "All packaging"}</option>{packs.map(p => <option key={p} value={p}>{t.packaging[p]}</option>)}</select></label>}
          {groups.length > 1 && <label><span className="sr-only">{id ? "Jenis minuman" : "Drink type"}</span><select value={group} onChange={e => setGroup(e.target.value)} className="catalog-select"><option value="">{id ? "Semua jenis" : "All types"}</option>{groups.map(g => <option key={g} value={g}>{g === "rtd" ? (id ? "Siap minum" : "Ready to drink") : (id ? "Seduh sendiri" : "Brew at home")}</option>)}</select></label>}
        </div>
      </div>
      {filtered.length ? <ul className="grid gap-x-7 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">{filtered.map(p => <li key={p.slug}><ProductCard product={p} showShopLink/></li>)}</ul> : <div role="status" className="rounded-3xl border border-dashed border-espresso-900/25 px-6 py-20 text-center"><h2 className="font-display text-2xl">{id ? "Belum ketemu yang pas?" : "Nothing here just yet."}</h2><p className="mt-3 text-espresso-600">{id ? "Coba kata lain atau hapus filter." : "Try another name or clear your filters."}</p><button className="btn-outline mt-6" onClick={() => { setQuery(""); setPackaging(""); setGroup(""); }}>{id ? "Hapus filter" : "Clear filters"}</button></div>}
    </div>
  </section>;
}

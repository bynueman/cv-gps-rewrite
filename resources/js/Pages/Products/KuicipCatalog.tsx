import type { ReactElement } from "react";
import { Head } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { ProductCatalog } from "@/Components/ProductCatalog";
import SiteLayout from "@/Layouts/SiteLayout";
import type { Product } from "@/types";
function Catalog({ products, seo }: { products: Product[]; seo: {title: string; description: string; canonical?: string} }) {
  const { lang } = useLang();
  return <><Head title={seo.title}><meta name="description" content={seo.description}/>{seo.canonical && <link rel="canonical" href={seo.canonical}/>}</Head>
    <header className="container-page pb-12 pt-32 sm:pt-40"><div className="grid items-end gap-6 border-b border-espresso-900/15 pb-12 lg:grid-cols-[1.5fr_1fr]"><div><p className="kicker">GPS FOOD / Kuicip</p><h1 className="mt-5 max-w-3xl font-display text-5xl leading-[1.02] sm:text-6xl lg:text-7xl">{lang === "id" ? "Renyahnya punya cerita." : "A little crunch. A lot of character."}</h1></div><p className="max-w-md text-lg leading-relaxed text-espresso-600">{lang === "id" ? "Pilihan rasa Kuicip untuk menemani jeda dan percakapanmu." : "Explore Kuicip flavours for your everyday breaks and conversations."}</p></div></header>
    <ProductCatalog products={products} brand="kuicip"/>
  </>;
}
Catalog.layout = (page: ReactElement) => <SiteLayout>{page}</SiteLayout>;
export default Catalog;

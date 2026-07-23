import type { ReactElement } from "react";
import { Head } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import { PageHeader } from "@/Components/PageHeader";
import { ProductCard } from "@/Components/ProductCard";
import SiteLayout from "@/Layouts/SiteLayout";
import type { Product } from "@/types";

type Seo = { title: string; description: string; canonical?: string };

/** Kuicip category page: full 8-flavor catalog. */
function KuicipCatalog({ products, seo }: { products: Product[]; seo: Seo }) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);

  return (
    <>
      <Head title={seo.title}>
        <meta name="description" content={seo.description} />
        {seo.canonical ? <link rel="canonical" href={seo.canonical} /> : null}
      </Head>
      <PageHeader
        kicker={t.kuicipPage.kicker}
        title={t.kuicipPage.title}
        lede={t.kuicipPage.subtitle}
        variant="kuicip"
        deps={[lang]}
      />

      <section ref={scope} className="pb-20 lg:pb-28">
        <div className="container-page">
          <h2 data-reveal className="font-display text-2xl font-semibold">
            {t.kuicipPage.catalogTitle}
          </h2>
          <ul className="mt-8 grid grid-cols-2 gap-5 md:grid-cols-3 lg:grid-cols-4 lg:gap-6">
            {products.map((p) => (
              <li key={p.slug} data-reveal>
                <ProductCard product={p} />
              </li>
            ))}
          </ul>
        </div>
      </section>
    </>
  );
}

KuicipCatalog.layout = (page: ReactElement) => <SiteLayout children={page} />;

export default KuicipCatalog;

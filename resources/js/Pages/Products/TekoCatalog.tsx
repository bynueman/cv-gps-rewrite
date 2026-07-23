import type { ReactElement } from "react";
import { Head } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import { PageHeader } from "@/Components/PageHeader";
import { ProductCard } from "@/Components/ProductCard";
import SiteLayout from "@/Layouts/SiteLayout";
import type { Product } from "@/types";

type Seo = { title: string; description: string; canonical?: string };

type PackagingGroups = {
  botol: Product[];
  kotak: Product[];
  toples: Product[];
  kemasan: Product[];
  besek: Product[];
};

/**
 * Putri Teko category page, organized by the real packaging forms in
 * the photo set: a dark brew panel first (kotak sachet, then toples
 * jars, then the practical kemasan pack and besek basket), with the
 * light RTD bottle panel last. Grouping is computed server-side
 * (ProductController@teko) in the fixed display order.
 */
function TekoCatalog({ packaging, seo }: { packaging: PackagingGroups; seo: Seo }) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);
  const { botol, kotak, toples, kemasan, besek } = packaging;

  return (
    <>
      <Head title={seo.title}>
        <meta name="description" content={seo.description} />
        {seo.canonical ? <link rel="canonical" href={seo.canonical} /> : null}
      </Head>
      <PageHeader
        kicker={t.tekoPage.kicker}
        title={t.tekoPage.title}
        lede={t.tekoPage.subtitle}
        variant="teko"
        deps={[lang]}
      />

      <section ref={scope} className="pb-20 lg:pb-28">
        <div className="container-page space-y-16">
          <div className="rounded-3xl bg-espresso-900 p-7 text-cream-100 sm:p-10">
            <h2 data-reveal className="font-display text-2xl font-semibold">
              {t.tekoPage.brewTitle}
            </h2>
            <p data-reveal className="mt-2 max-w-xl text-sm leading-relaxed text-cream-200/80">
              {t.tekoPage.brewBody}
            </p>

            <div className="mt-9">
              <h3 data-reveal className="font-display text-lg font-semibold text-turmeric">
                {t.tekoPage.packs.kotak.title}
              </h3>
              <p data-reveal className="mt-1 text-sm text-cream-200/70">
                {t.tekoPage.packs.kotak.body}
              </p>
              <ul className="mt-5 grid grid-cols-2 gap-5 lg:grid-cols-4">
                {kotak.map((p) => (
                  <li key={p.slug} data-reveal>
                    <ProductCard product={p} />
                  </li>
                ))}
              </ul>
            </div>

            <div className="mt-10">
              <h3 data-reveal className="font-display text-lg font-semibold text-turmeric">
                {t.tekoPage.packs.toples.title}
              </h3>
              <p data-reveal className="mt-1 text-sm text-cream-200/70">
                {t.tekoPage.packs.toples.body}
              </p>
              <ul className="mt-5 grid grid-cols-2 gap-5 lg:grid-cols-4">
                {toples.map((p) => (
                  <li key={p.slug} data-reveal>
                    <ProductCard product={p} />
                  </li>
                ))}
              </ul>
            </div>

            <div className="mt-10">
              <h3 data-reveal className="font-display text-lg font-semibold text-turmeric">
                {t.tekoPage.packs.kemasan.title}
              </h3>
              <p data-reveal className="mt-1 text-sm text-cream-200/70">
                {t.tekoPage.packs.kemasan.body}
              </p>
              <ul className="mt-5 grid gap-5 sm:grid-cols-2">
                {kemasan.map((p) => (
                  <li key={p.slug} data-reveal>
                    <ProductCard product={p} size="wide" showServing />
                  </li>
                ))}
              </ul>
            </div>

            <div className="mt-10">
              <h3 data-reveal className="font-display text-lg font-semibold text-turmeric">
                {t.tekoPage.packs.besek.title}
              </h3>
              <p data-reveal className="mt-1 text-sm text-cream-200/70">
                {t.tekoPage.packs.besek.body}
              </p>
              <ul className="mt-5 grid gap-5 sm:grid-cols-2">
                {besek.map((p) => (
                  <li key={p.slug} data-reveal>
                    <ProductCard product={p} size="wide" showServing />
                  </li>
                ))}
              </ul>
            </div>
          </div>

          <div className="rounded-3xl border border-espresso-900/10 bg-cream-50 p-7 sm:p-10">
            <h2 data-reveal className="font-display text-2xl font-semibold">
              {t.tekoPage.rtdTitle}
            </h2>
            <p data-reveal className="mt-2 max-w-xl text-sm leading-relaxed text-espresso-600">
              {t.tekoPage.rtdBody}
            </p>
            <ul className="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              {botol.map((p) => (
                <li key={p.slug} data-reveal>
                  <ProductCard product={p} showServing />
                </li>
              ))}
            </ul>
          </div>

          <p data-reveal className="text-xs italic text-espresso-500">
            {t.tekoPage.groupNote}
          </p>
        </div>
      </section>
    </>
  );
}

TekoCatalog.layout = (page: ReactElement) => <SiteLayout children={page} />;

export default TekoCatalog;

import type { ReactElement } from "react";
import { Link, Head, usePage } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import { PageHeader } from "@/Components/PageHeader";
import { ProductCard } from "@/Components/ProductCard";
import SiteLayout from "@/Layouts/SiteLayout";
import type { Product, SharedProps } from "@/types";

type Seo = { title: string; description: string; canonical?: string };

/** Category-first overview: two brand clusters, each with a short
 *  story, selected highlights, and a CTA to its category page. */
function Overview({
  featuredKuicip,
  featuredTekoRtd,
  featuredTekoBrew,
  seo,
}: {
  featuredKuicip: Product[];
  featuredTekoRtd: Product[];
  featuredTekoBrew: Product[];
  seo: Seo;
}) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);
  const { brands, brandAssets } = usePage<SharedProps>().props.site;
  const kuicip = brands.find((b) => b.key === "kuicip")!;
  const teko = brands.find((b) => b.key === "putri-teko")!;

  return (
    <>
      <Head title={seo.title}>
        <meta name="description" content={seo.description} />
        {seo.canonical ? <link rel="canonical" href={seo.canonical} /> : null}
      </Head>
      <PageHeader
        kicker={t.productsPage.kicker}
        title={t.productsPage.title}
        lede={t.productsPage.subtitle}
        deps={[lang]}
      />

      <section ref={scope} className="pb-20 lg:pb-28">
        <div className="container-page space-y-16">
          {/* Kuicip cluster */}
          <div className="rounded-3xl border border-espresso-900/10 bg-cream-50 p-7 sm:p-10">
            <div data-reveal className="flex flex-wrap items-center gap-x-5 gap-y-2">
              <img
                src={brandAssets.logos.kuicip}
                alt={`Logo ${kuicip.name}`}
                className="h-12 w-auto sm:h-14"
              />
              <span className="text-sm font-semibold uppercase tracking-[0.18em] text-espresso-600">
                {kuicip.tag[lang]}
              </span>
            </div>
            <p data-reveal className="mt-4 max-w-2xl text-base leading-relaxed text-espresso-600">
              {kuicip.story[lang]}
            </p>
            <p data-reveal className="mt-8 text-xs font-semibold uppercase tracking-[0.2em] text-espresso-500">
              {t.productsPage.highlightsLabel}
            </p>
            <ul className="mt-4 grid grid-cols-2 gap-5 lg:grid-cols-4">
              {featuredKuicip.map((p) => (
                <li key={p.slug} data-reveal>
                  <ProductCard product={p} />
                </li>
              ))}
            </ul>
            <div data-reveal className="mt-8">
              <Link href={kuicip.href} className="btn-primary">
                {t.common.exploreBrand} {kuicip.name} →
              </Link>
            </div>
          </div>

          {/* Putri Teko cluster */}
          <div className="rounded-3xl bg-espresso-900 p-7 text-cream-100 sm:p-10">
            <div data-reveal className="flex flex-wrap items-center gap-x-5 gap-y-3">
              <img
                src={brandAssets.logos["putri-teko"]}
                alt={`Logo ${teko.name}`}
                className="h-20 w-auto sm:h-24"
              />
              <div>
                <h2 className="font-display text-4xl font-bold tracking-tight text-turmeric">
                  {teko.name}
                </h2>
                <span className="text-sm font-semibold uppercase tracking-[0.18em] text-cream-200/70">
                  {teko.tag[lang]}
                </span>
              </div>
            </div>
            <p data-reveal className="mt-4 max-w-2xl text-base leading-relaxed text-cream-200/85">
              {teko.story[lang]}
            </p>
            <p data-reveal className="mt-8 text-xs font-semibold uppercase tracking-[0.2em] text-cream-200/60">
              {t.productsPage.highlightsLabel}
            </p>
            <ul className="mt-4 grid gap-5 sm:grid-cols-2">
              {[...featuredTekoRtd.slice(0, 1), ...featuredTekoBrew.slice(0, 1)].map((p) => (
                <li key={p.slug} data-reveal>
                  <ProductCard product={p} size="wide" showServing />
                </li>
              ))}
            </ul>
            <div data-reveal className="mt-8">
              <Link href={teko.href} className="btn-primary">
                {t.common.exploreBrand} {teko.name} →
              </Link>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}

Overview.layout = (page: ReactElement) => <SiteLayout children={page} />;

export default Overview;

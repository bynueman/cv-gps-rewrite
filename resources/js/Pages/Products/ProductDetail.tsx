import type { ReactElement } from "react";
import { Link, Head, usePage } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import { ProductPackshot } from "@/Components/ProductPackshot";
import { ProductCard } from "@/Components/ProductCard";
import { ExportCTA } from "@/Components/ExportCTA";
import { productInquiryUrl } from "@/lib/commerce";
import SiteLayout from "@/Layouts/SiteLayout";
import type { Product, SharedProps } from "@/types";

type Seo = { title: string; description: string; canonical?: string };

/**
 * Reusable product detail template. Kuicip pages lean on flavor
 * storytelling (personality + flavor notes); Putri Teko pages lean on
 * ingredients and serving. Both share the same structure. `related` is
 * computed server-side (Product::relatedTo scope) instead of client-side,
 * since the full product catalog isn't shipped to every page.
 */
function ProductDetail({ product, related, seo, seoEnglish }: { product: Product; related: Product[]; seo: Seo; seoEnglish?: Pick<Seo, "title" | "description"> }) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang, product.slug]);
  const { brands, company } = usePage<SharedProps>().props.site;

  const brand = brands.find((b) => b.key === product.brand)!;
  const isKuicip = product.brand === "kuicip";
  const whatsapp = productInquiryUrl(company.whatsapp, brand?.name || product.brand, product.name[lang], lang);

  return (
    <>
      <Head title={lang === "en" && seoEnglish ? seoEnglish.title : seo.title}>
        <meta name="description" content={lang === "en" && seoEnglish ? seoEnglish.description : seo.description} />
        {seo.canonical ? <link rel="canonical" href={seo.canonical} /> : null}
      </Head>
      <article ref={scope} className="pb-20 pt-28 sm:pt-36 lg:pb-28">
        <div className="container-page">
          <nav data-reveal aria-label="Breadcrumb" className="text-sm font-medium text-espresso-500">
            <Link href={brand.href} className="hover:text-gold-700">
              ← {t.common.backToProducts} {brand.name}
            </Link>
          </nav>

          {/* Hero: packshot on a soft colored plane + product info */}
          <div className="mt-8 grid items-start gap-12 lg:grid-cols-12 lg:gap-16">
            <div data-reveal className="relative lg:col-span-5">
              {product.image ? (
                <div className="relative">
                  <ProductPackshot
                    product={product}
                    lang={lang}
                    rounded="rounded-3xl"
                    className="!aspect-[3/4]"
                  />
                  {!isKuicip && product.packaging ? (
                    <span className="absolute left-4 top-4 rounded-full bg-cream-50/90 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-espresso-800 backdrop-blur-sm">
                      {t.packaging[product.packaging]}
                    </span>
                  ) : null}
                  <span
                    className="absolute bottom-0 left-8 right-8 h-1.5 rounded-t-full opacity-90"
                    style={{ backgroundColor: product.color }}
                    aria-hidden="true"
                  />
                </div>
              ) : (
                <div
                  className="relative flex items-end justify-center overflow-hidden rounded-3xl pb-0 pt-10"
                  style={{ backgroundColor: `${product.color}26` }}
                >
                  <div
                    className="absolute -left-10 -top-10 h-44 w-44 rounded-full opacity-20"
                    style={{ backgroundColor: product.color }}
                    aria-hidden="true"
                  />
                  <div className={`relative drop-shadow-xl ${isKuicip ? "w-[62%]" : "w-[46%]"} pb-6`}>
                    <ProductPackshot product={product} lang={lang} />
                  </div>
                </div>
              )}
              {product.netWeight || (isKuicip && brand.weight) ? (
                <p className="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-espresso-500">
                  {t.productDetail.weightLabel}: {product.netWeight || brand.weight}
                </p>
              ) : null}
            </div>

            <div className="lg:col-span-7">
              <p data-reveal className="kicker">
                {t.productDetail.categoryLabel} · {brand.name} — {brand.tag[lang]}
              </p>
              <h1
                data-reveal
                className="mt-3 font-display text-4xl font-semibold leading-[1.08] tracking-tight sm:text-5xl"
              >
                {product.name[lang]}
              </h1>
              <p
                data-reveal
                className="mt-3 text-sm font-semibold uppercase tracking-[0.16em]"
                style={{ color: product.colorDark }}
              >
                {product.personality[lang]}
                {product.serving ? ` · ${product.serving[lang]}` : ""}
              </p>

              <h2 data-reveal className="mt-8 font-display text-lg font-semibold">
                {t.productDetail.aboutTitle}
              </h2>
              <p data-reveal className="mt-2 max-w-2xl text-base leading-relaxed text-espresso-600">
                {product.description?.[lang] || product.short[lang]}
              </p>

              <div className="mt-8 flex flex-wrap gap-3">
                {product.shopeeUrl && <a href={product.shopeeUrl} target="_blank" rel="noopener noreferrer sponsored" className="btn-primary w-full whitespace-nowrap sm:w-auto">{lang === "id" ? "Beli di Shopee" : "Buy on Shopee"} ↗</a>}
                {whatsapp ? <a href={whatsapp} target="_blank" rel="noopener noreferrer" className="btn-outline w-full whitespace-nowrap sm:w-auto">{lang === "id" ? "Tanya Produk" : "Ask about this product"} ↗</a> : <Link href="/contact" className="btn-outline">{t.nav.contact}</Link>}
              </div>
              <div className="mt-8 grid gap-8 sm:grid-cols-2">
                <div data-reveal>
                  <h2 className="font-display text-lg font-semibold">{t.productDetail.highlightsTitle}</h2>
                  <ul className="mt-3 space-y-2.5">
                    {(product.highlights ?? []).map((h, i) => (
                      <li key={i} className="flex items-start gap-2.5 text-sm leading-relaxed text-espresso-700">
                        <span
                          className="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                          style={{ backgroundColor: product.color }}
                          aria-hidden="true"
                        />
                        {h[lang]}
                      </li>
                    ))}
                  </ul>
                </div>
                <div data-reveal>
                  <h2 className="font-display text-lg font-semibold">
                    {isKuicip ? t.productDetail.notesTitleKuicip : t.productDetail.notesTitleTeko}
                  </h2>
                  <ul className="mt-3 space-y-2.5">
                    {(product.notes ?? []).map((n, i) => (
                      <li key={i} className="flex items-start gap-2.5 text-sm leading-relaxed text-espresso-700">
                        <span
                          className="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                          style={{ backgroundColor: product.colorDark }}
                          aria-hidden="true"
                        />
                        {n[lang]}
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <div data-reveal className="mt-16">
            <ExportCTA
              title={t.productDetail.inquiryTitle}
              body={t.productDetail.inquiryBody}
              button={t.productDetail.inquiryButton}
            />
          </div>

          {related.length ? (
            <div className="mt-16">
              <h2 data-reveal className="font-display text-2xl font-semibold">
                {t.productDetail.relatedTitle}
              </h2>
              <ul className="mt-7 grid grid-cols-2 gap-5 lg:grid-cols-3 lg:gap-6">
                {related.map((p) => (
                  <li key={p.slug} data-reveal>
                    <ProductCard product={p} />
                  </li>
                ))}
              </ul>
            </div>
          ) : null}
        </div>
      </article>
    </>
  );
}

ProductDetail.layout = (page: ReactElement) => <SiteLayout children={page} />;

export default ProductDetail;

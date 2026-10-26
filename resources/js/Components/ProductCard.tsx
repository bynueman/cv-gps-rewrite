import { localized, LocalizedText } from "@/lib/localized";
import { Link } from "@inertiajs/react";
import type { Product } from "@/types";
import { useLang } from "@/lib/i18n";
import { ProductPackshot } from "@/Components/ProductPackshot";

/**
 * Catalog/product card. The real studio shots share a warm cream
 * backdrop, so the photo fills the card frame edge-to-edge (with a
 * slow zoom on hover) instead of floating on a tinted plane. A small
 * chip shows configured net weight or the real
 * botol/kotak/toples/kemasan forms for Putri Teko.
 * `size="wide"` produces the horizontal bento variant.
 */
export function ProductCard({
  product,
  size = "default",
  showServing = false,
  showShopLink = false,
}: {
  product: Product;
  size?: "default" | "wide";
  showServing?: boolean;
  showShopLink?: boolean;
}) {
  const { lang, t } = useLang();
  const href = `/products/${product.brand}/${product.slug}`;

  const chip =
    product.netWeight || (product.packaging ? t.packaging[product.packaging] : null);

  if (size === "wide") {
    return (
      <Link
        href={href}
        className="group relative flex h-full overflow-hidden rounded-3xl border border-espresso-900/10 bg-cream-50 transition-all duration-300 hover:-translate-y-1 hover:shadow-lift"
      >
        <div className="relative w-[42%] min-h-[11rem] shrink-0 self-stretch overflow-hidden">
          {product.image ? (
            <img
              src={product.imageThumb || product.image}
              loading="lazy"
              decoding="async"
              alt={
                product.brand === "kuicip"
                  ? `Kuicip ${localized(product.name, lang)}`
                  : `Putri Teko ${localized(product.name, lang)}`
              }
              className="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.06]"
            />
          ) : (
            <div className="flex h-full items-center justify-center p-3">
              <ProductPackshot product={product} lang={lang} className="w-full" />
            </div>
          )}
          {chip ? (
            <span className="absolute left-2.5 top-2.5 rounded-full bg-cream-50/90 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-espresso-800 backdrop-blur-sm">
              {chip}
            </span>
          ) : null}
          <span
            className="absolute bottom-0 left-4 right-4 h-1 rounded-t-full opacity-80"
            style={{ backgroundColor: product.color }}
            aria-hidden="true"
          />
        </div>
        <div className="flex min-w-0 grow flex-col justify-center p-6 sm:p-7">
          <p className="text-xs font-semibold uppercase tracking-[0.16em]" style={{ color: product.colorDark }}>
            <LocalizedText value={product.personality} lang={lang}/>
          </p>
          <h3 className="mt-1.5 font-display text-xl font-semibold leading-snug text-espresso-950">
            <LocalizedText value={product.name} lang={lang}/>
          </h3>
          <p className="mt-2 text-sm leading-relaxed text-espresso-600"><LocalizedText value={product.short} lang={lang}/></p>
          {showServing && product.serving ? (
            <p className="mt-2 text-xs font-medium text-espresso-500"><LocalizedText value={product.serving} lang={lang}/></p>
          ) : null}
          <span className="mt-3 inline-block text-sm font-semibold text-gold-600">
            {t.common.viewDetail} →
          </span>
        </div>
      </Link>
    );
  }

  const content = (
    <>
      <div className="relative">
        <ProductPackshot product={product} lang={lang} zoom />
        {chip ? (
          <span className="absolute left-2.5 top-2.5 rounded-full bg-cream-50/90 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-espresso-800 backdrop-blur-sm">
            {chip}
          </span>
        ) : null}
        <span
          className="absolute bottom-0 left-4 right-4 h-1 rounded-t-full opacity-80"
          style={{ backgroundColor: product.color }}
          aria-hidden="true"
        />
      </div>
      <div className="flex grow flex-col px-6 pb-7 pt-6">
        <p className="text-[11px] font-semibold uppercase tracking-[0.15em]" style={{ color: product.colorDark }}>
          <LocalizedText value={product.personality} lang={lang}/>
        </p>
        <h3 className="mt-2 font-display text-2xl leading-snug text-espresso-950">
          <LocalizedText value={product.name} lang={lang}/>
        </h3>
        <p className="mt-1.5 grow text-sm leading-relaxed text-espresso-600"><LocalizedText value={product.short} lang={lang}/></p>
        {showServing && product.serving ? (
          <p className="mt-2 text-xs font-medium text-espresso-500"><LocalizedText value={product.serving} lang={lang}/></p>
        ) : null}
        <span className="mt-3 text-sm font-semibold text-gold-600">{t.common.viewDetail} →</span>
      </div>
    </>
  );
  const cardClass = "group flex h-full flex-col overflow-hidden rounded-3xl border border-espresso-900/10 bg-cream-50 transition-colors hover:border-gold-600";

  if (showShopLink && product.shopeeUrl) {
    return (
      <article className={cardClass}>
        <Link href={href} className="flex grow flex-col">{content}</Link>
        <a href={product.shopeeUrl} target="_blank" rel="noopener noreferrer sponsored"
          className="btn-outline mx-6 mb-6 whitespace-nowrap !px-3 !py-2.5 text-sm">
          {lang === "id" ? "Beli di Shopee" : "Buy on Shopee"} ↗
        </a>
      </article>
    );
  }
  return <Link href={href} className={cardClass}>{content}</Link>;
}

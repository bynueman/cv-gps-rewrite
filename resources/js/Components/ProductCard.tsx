import { Link } from "@inertiajs/react";
import type { Product } from "@/types";
import { useLang } from "@/lib/i18n";
import { ProductPackshot } from "@/Components/ProductPackshot";

/**
 * Catalog/product card. The real studio shots share a warm cream
 * backdrop, so the photo fills the card frame edge-to-edge (with a
 * slow zoom on hover) instead of floating on a tinted plane. A small
 * chip states the packaging form: 70 gr ziplock for Kuicip, the real
 * botol/kotak/toples/kemasan forms for Putri Teko.
 * `size="wide"` produces the horizontal bento variant.
 */
export function ProductCard({
  product,
  size = "default",
  showServing = false,
}: {
  product: Product;
  size?: "default" | "wide";
  showServing?: boolean;
}) {
  const { lang, t } = useLang();
  const href = `/products/${product.brand}/${product.slug}`;

  const chip =
    product.brand === "kuicip" ? "70 gr" : product.packaging ? t.packaging[product.packaging] : null;

  if (size === "wide") {
    return (
      <Link
        href={href}
        className="group relative flex h-full overflow-hidden rounded-3xl border border-espresso-900/10 bg-cream-50 transition-all duration-300 hover:-translate-y-1 hover:shadow-lift"
      >
        <div className="relative w-[42%] min-h-[11rem] shrink-0 self-stretch overflow-hidden">
          {product.image ? (
            <img
              src={product.image}
              alt={
                product.brand === "kuicip"
                  ? `Kuicip ${product.name[lang]}`
                  : `Putri Teko ${product.name[lang]}`
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
            {product.personality[lang]}
          </p>
          <h3 className="mt-1.5 font-display text-xl font-semibold leading-snug text-espresso-950">
            {product.name[lang]}
          </h3>
          <p className="mt-2 text-sm leading-relaxed text-espresso-600">{product.short[lang]}</p>
          {showServing && product.serving ? (
            <p className="mt-2 text-xs font-medium text-espresso-500">{product.serving[lang]}</p>
          ) : null}
          <span className="mt-3 inline-block text-sm font-semibold text-gold-600">
            {t.common.viewDetail} →
          </span>
        </div>
      </Link>
    );
  }

  return (
    <Link
      href={href}
      className="group flex h-full flex-col rounded-3xl border border-espresso-900/10 bg-cream-50 p-3 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lift"
    >
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
      <div className="flex grow flex-col px-2.5 pb-2.5 pt-4">
        <p className="text-[11px] font-semibold uppercase tracking-[0.15em]" style={{ color: product.colorDark }}>
          {product.personality[lang]}
        </p>
        <h3 className="mt-1 font-display text-lg font-semibold leading-snug text-espresso-950">
          {product.name[lang]}
        </h3>
        <p className="mt-1.5 grow text-sm leading-relaxed text-espresso-600">{product.short[lang]}</p>
        {showServing && product.serving ? (
          <p className="mt-2 text-xs font-medium text-espresso-500">{product.serving[lang]}</p>
        ) : null}
        <span className="mt-3 text-sm font-semibold text-gold-600">{t.common.viewDetail} →</span>
      </div>
    </Link>
  );
}

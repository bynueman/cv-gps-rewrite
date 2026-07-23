import { Link, usePage } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import type { BrandKey, Product, SharedProps } from "@/types";

/**
 * Featured-products bento grid: one hero card plus four upright
 * supporting cards, dark section bg. `hero`/`supporting` are resolved
 * server-side (HomeController) from the same fixed (brand, slug) pairs
 * the original app hardcoded client-side.
 */
export function FeaturedProducts({ hero, supporting }: { hero: Product | null; supporting: Product[] }) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);
  const { brands } = usePage<SharedProps>().props.site;
  const kuicipBrand = brands.find((b) => b.key === "kuicip")!;
  const tekoBrand = brands.find((b) => b.key === "putri-teko")!;

  const tagFor = (brand: BrandKey, packaging?: string | null) =>
    brand === "kuicip" ? kuicipBrand.tag[lang] : t.packaging[packaging as keyof typeof t.packaging];
  const accentFor = (brand: BrandKey) => (brand === "kuicip" ? "text-homeTerracotta" : "text-homeSage");

  return (
    <section ref={scope} className="bg-homeInk px-6 py-32 sm:px-14 lg:py-36">
      <div className="mx-auto max-w-6xl">
        <div className="mb-12 flex flex-wrap items-end justify-between gap-6">
          <div>
            <p data-reveal className="text-[13px] font-extrabold uppercase tracking-[0.08em] text-homeGold">
              {t.featured.kicker}
            </p>
            <h2 data-reveal className="mt-3 max-w-xl font-display text-4xl text-homeBg sm:text-[46px]">
              {t.featured.title}
            </h2>
          </div>
          <Link data-reveal href="/products" className="font-bold text-homeGold">
            {t.common.seeAllProducts} →
          </Link>
        </div>

        <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
          {hero ? (
            <Link
              data-reveal
              href={`/products/${hero.brand}/${hero.slug}`}
              className="group flex flex-col gap-4 rounded-[26px] bg-homeCard p-6 transition-transform duration-300 hover:-translate-y-1.5 lg:row-span-2"
            >
              <div className="relative min-h-[280px] flex-1 overflow-hidden rounded-[18px] bg-homePanel">
                {hero.image ? (
                  <img
                    src={hero.image}
                    alt={hero.name[lang]}
                    className="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.05]"
                  />
                ) : null}
              </div>
              <div>
                <p className={`mb-1 text-[11px] font-extrabold uppercase tracking-[0.04em] ${accentFor(hero.brand)}`}>
                  {tekoBrand.name} · {tagFor(hero.brand, hero.packaging)}
                </p>
                <p className="font-display text-2xl text-homeInk">{hero.name[lang]}</p>
                <p className="mt-1 text-sm text-homeInk2">{hero.short[lang]}</p>
              </div>
            </Link>
          ) : null}

          <div className="grid grid-cols-2 gap-5">
            {supporting.map((product) => (
              <Link
                key={`${product.brand}-${product.slug}`}
                data-reveal
                href={`/products/${product.brand}/${product.slug}`}
                className="group flex flex-col gap-3 rounded-[22px] bg-homeCard p-4 transition-transform duration-300 hover:-translate-y-1.5"
              >
                <div className="relative aspect-[3/4] overflow-hidden rounded-2xl bg-homePanel">
                  {product.image ? (
                    <img
                      src={product.image}
                      alt={product.name[lang]}
                      className="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.05]"
                    />
                  ) : null}
                </div>
                <div>
                  <p className={`mb-1 text-[11px] font-extrabold uppercase tracking-[0.04em] ${accentFor(product.brand)}`}>
                    {tagFor(product.brand, product.packaging)}
                  </p>
                  <p className="font-display text-lg text-homeInk">{product.name[lang]}</p>
                </div>
              </Link>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

import { Link, usePage } from "@inertiajs/react";
import { useLang, type Lang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import type { Product, SharedProps } from "@/types";

/** Decorative flavor-name pills — proper nouns, not linked product cards. */
const KUICIP_TAGS: Record<Lang, string[]> = {
  id: ["Original", "Balado", "Rendang", "Spicy Mala", "Chili Lime"],
  en: ["Original", "Balado", "Rendang", "Spicy Mala", "Chili Lime"],
};
const PT_TAGS: Record<Lang, string[]> = {
  id: ["Beras Kencur", "Wedang Uwuh", "Jahe Merah", "Gula Asam"],
  en: ["Beras Kencur", "Wedang Uwuh", "Red Ginger", "Gula Asam"],
};

/**
 * "Two Brand Worlds" split panels. `kuicip1`/`kuicip2`/`teko1`/`teko2`
 * are resolved server-side (HomeController) from the same fixed
 * (brand, slug) pairs the original app hardcoded client-side.
 */
export function ProductFamilies({
  kuicip1,
  kuicip2,
  teko1,
  teko2,
}: {
  kuicip1: Product | null;
  kuicip2: Product | null;
  teko1: Product | null;
  teko2: Product | null;
}) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);
  const { brands } = usePage<SharedProps>().props.site;
  const kuicipBrand = brands.find((b) => b.key === "kuicip")!;
  const tekoBrand = brands.find((b) => b.key === "putri-teko")!;

  return (
    <section ref={scope} className="relative grid grid-cols-1 lg:grid-cols-2">
      <div className="relative flex flex-col items-center gap-5 overflow-hidden bg-homeKuicipPanel px-6 py-24 text-center sm:px-14 lg:py-28">
        <div aria-hidden="true" className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-homeGold/50" />
        <p data-reveal className="relative font-display text-5xl text-homeTerracotta">
          {kuicipBrand.name}
        </p>
        <p data-reveal className="relative max-w-sm text-base text-homeInk2">
          {t.families.kuicipTagline}
        </p>
        <div className="relative my-4 flex items-end justify-center gap-1">
          {kuicip1?.image ? (
            <div className="relative z-0 h-56 w-44 -rotate-6 overflow-hidden rounded-[18px] drop-shadow-[0_20px_30px_oklch(27%_0.045_50_/_0.25)]">
              <img src={kuicip1.image} alt={kuicip1.name[lang]} className="absolute inset-0 h-full w-full object-cover" />
            </div>
          ) : null}
          {kuicip2?.image ? (
            <div className="relative z-10 -ml-10 h-48 w-40 rotate-6 overflow-hidden rounded-[18px] drop-shadow-[0_20px_30px_oklch(27%_0.045_50_/_0.25)]">
              <img src={kuicip2.image} alt={kuicip2.name[lang]} className="absolute inset-0 h-full w-full object-cover" />
            </div>
          ) : null}
        </div>
        <div data-reveal className="relative flex flex-wrap justify-center gap-2.5">
          {KUICIP_TAGS[lang].map((tag) => (
            <span key={tag} className="rounded-full bg-homeBg px-4 py-2 text-[13px] font-bold text-homeTerracotta">
              {tag}
            </span>
          ))}
        </div>
        <Link
          data-reveal
          href={kuicipBrand.href}
          className="relative mt-1 rounded-full bg-homeTerracotta px-[1.625rem] py-3.5 text-sm font-bold text-homeBg transition-colors hover:bg-homeTerracottaDark"
        >
          {t.families.kuicipCta}
        </Link>
      </div>

      <div className="relative flex flex-col items-center gap-5 overflow-hidden bg-homeTekoPanel px-6 py-24 text-center sm:px-14 lg:py-28">
        <div aria-hidden="true" className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-homeSage/30" />
        <p data-reveal className="relative font-display text-5xl text-homeSage">
          {tekoBrand.name}
        </p>
        <p data-reveal className="relative max-w-sm text-base text-homeInk2">
          {t.families.tekoTagline}
        </p>
        <div className="relative my-4 flex items-end justify-center gap-1">
          {teko1?.image ? (
            <div className="relative z-0 h-52 w-40 -rotate-6 overflow-hidden rounded-[18px] drop-shadow-[0_20px_30px_oklch(27%_0.045_50_/_0.25)]">
              <img src={teko1.image} alt={teko1.name[lang]} className="absolute inset-0 h-full w-full object-cover" />
            </div>
          ) : null}
          {teko2?.image ? (
            <div className="relative z-10 -ml-10 h-48 w-40 rotate-6 overflow-hidden rounded-[18px] drop-shadow-[0_20px_30px_oklch(27%_0.045_50_/_0.25)]">
              <img src={teko2.image} alt={teko2.name[lang]} className="absolute inset-0 h-full w-full object-cover" />
            </div>
          ) : null}
        </div>
        <div data-reveal className="relative flex flex-wrap justify-center gap-2.5">
          {PT_TAGS[lang].map((tag) => (
            <span key={tag} className="rounded-full bg-homeBg px-4 py-2 text-[13px] font-bold text-homeSage">
              {tag}
            </span>
          ))}
        </div>
        <Link
          data-reveal
          href={tekoBrand.href}
          className="relative mt-1 rounded-full bg-homeSage px-[1.625rem] py-3.5 text-sm font-bold text-homeBg transition-colors hover:bg-homeSageDark"
        >
          {t.families.tekoCta}
        </Link>
      </div>

      <div className="pointer-events-none absolute left-1/2 top-1/2 z-20 hidden h-36 w-36 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-homeInk p-4 text-center text-[13px] font-bold text-homeBg shadow-[0_20px_40px_-14px_oklch(27%_0.045_50_/_0.5)] lg:flex">
        {t.families.center}
      </div>
    </section>
  );
}

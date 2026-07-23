import { Link } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";

/**
 * Export teaser: text + 3 stat cards on the left, a dark panel with
 * static (visual-only) field rows and a CTA on the right. This is a
 * teaser for the full `/export` page — the field rows aren't a real
 * form, matching the original.
 */
export function ExportPreview() {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);

  return (
    <section ref={scope} className="mx-auto max-w-6xl px-6 py-32 sm:px-14 lg:py-36">
      <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1.4fr_1fr]">
        <div>
          <p data-reveal className="text-[13px] font-extrabold uppercase tracking-[0.08em] text-homeTerracotta">
            {t.homeExport.kicker}
          </p>
          <h2 data-reveal className="mt-3 max-w-lg font-display text-[42px] leading-tight text-homeInk">
            {t.homeExport.title}
          </h2>
          <p data-reveal className="mt-4 max-w-lg text-base leading-relaxed text-homeInk2">
            {t.homeExport.desc}
          </p>
          <div className="mt-8 grid gap-5 sm:grid-cols-3">
            {t.homeExport.cards.map((card) => (
              <div key={card.title} data-reveal className="flex flex-col gap-2 rounded-[22px] bg-homePanel p-6">
                <p className="font-display text-[19px] text-homeInk">{card.title}</p>
                <p className="text-[13px] leading-relaxed text-homeInk2">{card.desc}</p>
              </div>
            ))}
          </div>
        </div>

        <div
          data-reveal
          className="flex flex-col gap-5 rounded-[28px] bg-homeInk p-9 shadow-[0_30px_60px_-24px_oklch(27%_0.045_50_/_0.45)]"
        >
          <p className="font-display text-2xl text-homeBg">{t.homeExport.panelTitle}</p>
          <p className="text-sm leading-relaxed text-homeBg/80">{t.homeExport.panelDesc}</p>
          <div className="mt-1 flex flex-col gap-2.5">
            {[t.homeExport.fieldName, t.homeExport.fieldEmail, t.homeExport.fieldCountry].map((field) => (
              <div
                key={field}
                className="rounded-xl border border-homeBg/20 bg-homeBg/[0.08] px-4 py-3.5 text-[13px] text-homeBg/60"
              >
                {field}
              </div>
            ))}
          </div>
          <Link
            href="/export"
            className="mt-2 rounded-full bg-homeGold px-6 py-3.5 text-center text-sm font-extrabold text-homeInk transition-colors hover:bg-homeGoldLight"
          >
            {t.homeExport.panelCta}
          </Link>
        </div>
      </div>
    </section>
  );
}

import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";

/**
 * Simple "About" layout: statement + body on the left, an offset fact
 * panel on the right.
 */
export function CompanyIntro() {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);

  return (
    <section id="about" ref={scope} className="mx-auto max-w-6xl px-6 py-32 sm:px-14 lg:py-36">
      <div className="grid grid-cols-1 items-start gap-12 lg:grid-cols-12 lg:gap-16">
        <div className="lg:col-span-7">
          <p data-reveal className="text-[13px] font-extrabold uppercase tracking-[0.08em] text-homeTerracotta">
            {t.intro.kicker}
          </p>
          <h2 data-reveal className="mt-4 font-display text-3xl leading-tight text-homeInk sm:text-4xl">
            {t.intro.title}
          </h2>
          <p data-reveal className="mt-6 max-w-2xl text-base leading-relaxed text-homeInk2 sm:text-lg">
            {t.intro.body}
          </p>
        </div>

        <div data-reveal className="lg:col-span-5 lg:mt-4">
          <dl className="relative rounded-3xl border border-homeInk/10 bg-homeCard p-7 sm:p-8">
            <span className="absolute -left-3 -top-3 h-10 w-10 rounded-2xl bg-homeGold/70" aria-hidden="true" />
            {t.intro.facts.map((fact, i) => (
              <div
                key={fact.label}
                className={`flex items-baseline justify-between gap-4 py-3.5 ${
                  i > 0 ? "border-t border-homeInk/10" : ""
                }`}
              >
                <dt className="text-xs font-semibold uppercase tracking-[0.16em] text-homeInk2">
                  {fact.label}
                </dt>
                <dd className="text-right text-sm font-semibold text-homeInk">{fact.value}</dd>
              </div>
            ))}
          </dl>
        </div>
      </div>
    </section>
  );
}

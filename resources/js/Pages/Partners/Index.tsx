import { useMemo, useState, type ReactElement } from "react";
import { Head } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import { PageHeader } from "@/Components/PageHeader";
import SiteLayout from "@/Layouts/SiteLayout";
import type { Partner } from "@/types";

type Seo = { title: string; description: string; canonical?: string };

const CATEGORY_ORDER: Partner["category"][] = ["ritel", "hotel", "restaurant", "oleh-oleh", "cakery", "institusi"];

function Partners({ partners, seo }: { partners: Partner[]; seo: Seo }) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);
  const [activeCategory, setActiveCategory] = useState<Partner["category"] | "all">("all");

  const filtered = useMemo(
    () => (activeCategory === "all" ? partners : partners.filter((p) => p.category === activeCategory)),
    [partners, activeCategory]
  );

  const availableCategories = useMemo(
    () => CATEGORY_ORDER.filter((c) => partners.some((p) => p.category === c)),
    [partners]
  );

  return (
    <>
      <Head title={seo.title}>
        <meta name="description" content={seo.description} />
        {seo.canonical ? <link rel="canonical" href={seo.canonical} /> : null}
      </Head>
      <PageHeader
        kicker={t.partnersPage.kicker}
        title={t.partnersPage.title}
        lede={t.partnersPage.subtitle}
        deps={[lang]}
      />

      <section ref={scope} className="pb-20 lg:pb-28">
        <div className="container-page">
          <div className="flex flex-wrap gap-2" role="tablist" aria-label={t.partnersPage.kicker}>
            <button
              type="button"
              role="tab"
              aria-selected={activeCategory === "all"}
              onClick={() => setActiveCategory("all")}
              className={`rounded-full px-4 py-2 text-sm font-semibold transition-colors ${
                activeCategory === "all"
                  ? "bg-espresso-900 text-cream-50"
                  : "bg-cream-200 text-espresso-700 hover:bg-cream-300"
              }`}
            >
              {t.partnersPage.all}
            </button>
            {availableCategories.map((category) => (
              <button
                key={category}
                type="button"
                role="tab"
                aria-selected={activeCategory === category}
                onClick={() => setActiveCategory(category)}
                className={`rounded-full px-4 py-2 text-sm font-semibold transition-colors ${
                  activeCategory === category
                    ? "bg-espresso-900 text-cream-50"
                    : "bg-cream-200 text-espresso-700 hover:bg-cream-300"
                }`}
              >
                {t.partnersPage.categories[category]}
              </button>
            ))}
          </div>

          {filtered.length === 0 ? (
            <p data-reveal className="mt-12 text-sm text-espresso-600">
              {t.partnersPage.empty}
            </p>
          ) : (
            <div data-reveal className="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
              {filtered.map((partner) => (
                <div
                  key={`${partner.category}-${partner.name}`}
                  className="flex flex-col items-center gap-3 rounded-2xl border border-espresso-900/10 bg-cream-50 p-5 text-center"
                >
                  <span className="flex h-16 w-full items-center justify-center">
                    {partner.logo ? (
                      <img src={partner.logo} alt={partner.name} className="max-h-16 max-w-full object-contain" />
                    ) : (
                      <span className="font-display text-sm font-semibold text-espresso-700">{partner.name}</span>
                    )}
                  </span>
                  <p className="text-xs font-medium text-espresso-600">{partner.name}</p>
                </div>
              ))}
            </div>
          )}
        </div>
      </section>
    </>
  );
}

Partners.layout = (page: ReactElement) => <SiteLayout children={page} />;

export default Partners;

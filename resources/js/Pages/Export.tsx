import type { ReactElement } from "react";
import { Head } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import { PageHeader } from "@/Components/PageHeader";
import { ExportCTA } from "@/Components/ExportCTA";
import SiteLayout from "@/Layouts/SiteLayout";

type Seo = { title: string; description: string; canonical?: string };

/**
 * Full export & partnership page. Deliberately claim-free: portfolio
 * exploration, packaging presentation, collaboration, and a clear
 * inquiry path — B2B tone throughout.
 */
function Export({ seo }: { seo: Seo }) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);

  return (
    <>
      <Head title={seo.title}>
        <meta name="description" content={seo.description} />
        {seo.canonical ? <link rel="canonical" href={seo.canonical} /> : null}
      </Head>
      <PageHeader
        kicker={t.exportSection.kicker}
        title={t.exportSection.title}
        lede={t.exportSection.pageIntro}
        deps={[lang]}
      />

      <section ref={scope} className="pb-20 lg:pb-28">
        <div className="container-page">
          <div className="grid gap-5 sm:grid-cols-2 lg:gap-6">
            {t.exportSection.blocks.map((block, i) => (
              <article
                key={block.title}
                data-reveal
                className="rounded-2xl border border-espresso-900/10 bg-cream-50 p-6 transition-colors duration-300 hover:border-espresso-900/25 sm:p-7"
              >
                <span className="font-display text-sm font-semibold text-gold-600">
                  {String(i + 1).padStart(2, "0")}
                </span>
                <h2 className="mt-2 font-display text-lg font-semibold">{block.title}</h2>
                <p className="mt-2 text-sm leading-relaxed text-espresso-600">{block.body}</p>
              </article>
            ))}
          </div>

          <h2 data-reveal className="mt-16 font-display text-2xl font-semibold">
            {t.exportSection.processTitle}
          </h2>
          <ol className="mt-6 grid gap-0 border-t border-espresso-900/10 lg:grid-cols-4 lg:gap-6 lg:border-t-0">
            {t.exportSection.process.map((step, i) => (
              <li
                key={step.title}
                data-reveal
                className="border-b border-espresso-900/10 py-5 lg:border-b-0 lg:border-t lg:pt-6"
              >
                <span className="font-display text-2xl font-semibold text-gold-600">
                  {String(i + 1).padStart(2, "0")}
                </span>
                <h3 className="mt-2 font-display text-base font-semibold">{step.title}</h3>
                <p className="mt-1.5 text-sm leading-relaxed text-espresso-600">{step.body}</p>
              </li>
            ))}
          </ol>

          <div data-reveal className="mt-14">
            <ExportCTA />
          </div>
        </div>
      </section>
    </>
  );
}

Export.layout = (page: ReactElement) => <SiteLayout children={page} />;

export default Export;

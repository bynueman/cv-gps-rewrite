import type { ReactElement } from "react";
import { Head } from "@inertiajs/react";
import { ShieldCheck } from "lucide-react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import { PageHeader } from "@/Components/PageHeader";
import { ExportCTA } from "@/Components/ExportCTA";
import SiteLayout from "@/Layouts/SiteLayout";

type Seo = { title: string; description: string; canonical?: string };

/**
 * Legalitas & Sertifikasi: sibling of /export. Content is intentionally
 * an empty/placeholder state until actual certification documents and
 * logos are supplied — see the fostered-by footer badges for the
 * pattern this will follow once populated.
 */
function ExportLegality({ seo }: { seo: Seo }) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);
  const s = t.exportLegalitySection;

  return (
    <>
      <Head title={seo.title}>
        <meta name="description" content={seo.description} />
        {seo.canonical ? <link rel="canonical" href={seo.canonical} /> : null}
      </Head>
      <PageHeader kicker={s.kicker} title={s.title} lede={s.subtitle} deps={[lang]} />

      <section ref={scope} className="pb-20 lg:pb-28">
        <div className="container-page">
          <div
            data-reveal
            className="flex flex-col items-center gap-3 rounded-3xl border border-dashed border-espresso-900/15 bg-cream-50 px-6 py-16 text-center"
          >
            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-espresso-900/5 text-espresso-500">
              <ShieldCheck className="h-6 w-6" aria-hidden="true" />
            </span>
            <p className="font-display text-base font-semibold text-espresso-950">{s.emptyTitle}</p>
            <p className="max-w-sm text-sm leading-relaxed text-espresso-600">{s.emptyBody}</p>
          </div>

          <div data-reveal className="mt-14">
            <ExportCTA title={s.ctaTitle} body={s.ctaBody} button={s.ctaButton} />
          </div>
        </div>
      </section>
    </>
  );
}

ExportLegality.layout = (page: ReactElement) => <SiteLayout children={page} />;

export default ExportLegality;

import type { ReactElement } from "react";
import { Head } from "@inertiajs/react";
import { ShieldCheck, FileText, ExternalLink, CalendarClock } from "lucide-react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import { PageHeader } from "@/Components/PageHeader";
import { ExportCTA } from "@/Components/ExportCTA";
import SiteLayout from "@/Layouts/SiteLayout";

type Seo = { title: string; description: string; canonical?: string };

type Certification = {
  id: number;
  name: string;
  issuer: string | null;
  category: string;
  logo: string | null;
  pdf_url: string | null;
  valid_until: string | null;
};

const CATEGORY_COLORS: Record<string, { bg: string; text: string; dot: string }> = {
  legal:  { bg: "bg-blue-100",    text: "text-blue-800",   dot: "bg-blue-500" },
  halal:  { bg: "bg-green-100",   text: "text-green-800",  dot: "bg-green-500" },
  bpom:   { bg: "bg-orange-100",  text: "text-orange-800", dot: "bg-orange-500" },
  pirt:   { bg: "bg-purple-100",  text: "text-purple-800", dot: "bg-purple-500" },
  other:  { bg: "bg-cream-200",   text: "text-espresso-600", dot: "bg-espresso-400" },
};

function CertificationCard({
  cert,
  categoryLabel,
  pdfButton,
  validUntilLabel,
}: {
  cert: Certification;
  categoryLabel: string;
  pdfButton: string;
  validUntilLabel: string;
}) {
  const colors = CATEGORY_COLORS[cert.category] ?? CATEGORY_COLORS.other;

  const formattedDate = cert.valid_until
    ? new Date(cert.valid_until).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
      })
    : null;

  return (
    <article className="group flex flex-col rounded-2xl border border-espresso-900/10 bg-cream-50 p-5 transition-all duration-300 hover:border-espresso-900/20 hover:shadow-sm">
      {/* Category badge */}
      <span
        className={`inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ${colors.bg} ${colors.text}`}
      >
        <span className={`h-1.5 w-1.5 rounded-full ${colors.dot}`} aria-hidden="true" />
        {categoryLabel}
      </span>

      {/* Logo + name */}
      <div className="mt-4 flex items-center gap-4">
        <span className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-espresso-900/10 bg-white p-2">
          {cert.logo ? (
            <img
              src={cert.logo}
              alt={cert.name}
              className="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105"
            />
          ) : (
            <ShieldCheck className="h-7 w-7 text-espresso-300" aria-hidden="true" />
          )}
        </span>

        <div className="min-w-0 flex-1">
          <h2 className="font-display text-base font-semibold leading-snug text-espresso-950">
            {cert.name}
          </h2>
          {cert.issuer ? (
            <p className="mt-0.5 text-sm text-espresso-500">{cert.issuer}</p>
          ) : null}
        </div>
      </div>

      {/* Valid until badge */}
      {formattedDate ? (
        <div className="mt-3 inline-flex w-fit items-center gap-1.5 rounded-lg bg-espresso-900/5 px-2.5 py-1.5 text-xs text-espresso-600">
          <CalendarClock className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          <span>
            {validUntilLabel}:{" "}
            <span className="font-semibold">{formattedDate}</span>
          </span>
        </div>
      ) : null}

      {/* PDF CTA — pushed to bottom */}
      {cert.pdf_url ? (
        <div className="mt-auto pt-4">
          <a
            href={cert.pdf_url}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-2 rounded-xl border border-espresso-900/15 bg-white px-4 py-2 text-sm font-semibold text-espresso-900 transition-colors hover:border-espresso-900/30 hover:bg-cream-100"
          >
            <FileText className="h-4 w-4 text-espresso-500" aria-hidden="true" />
            {pdfButton}
            <ExternalLink className="h-3.5 w-3.5 text-espresso-400" aria-hidden="true" />
          </a>
        </div>
      ) : null}
    </article>
  );
}

/**
 * Legalitas & Sertifikasi — renders DB-driven certifications from admin CMS.
 * Falls back to the existing empty state when no active items exist.
 */
function ExportLegality({
  seo,
  certifications,
}: {
  seo: Seo;
  certifications: Certification[];
}) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang, certifications]);
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
          {certifications.length === 0 ? (
            /* Empty state — identical to original placeholder */
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
          ) : (
            <div
              data-reveal
              className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
              {certifications.map((cert) => (
                <CertificationCard
                  key={cert.id}
                  cert={cert}
                  categoryLabel={s.categories[cert.category as keyof typeof s.categories] ?? cert.category}
                  pdfButton={s.pdfButton}
                  validUntilLabel={s.validUntilLabel}
                />
              ))}
            </div>
          )}

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

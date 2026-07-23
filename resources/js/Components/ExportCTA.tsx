import { Link } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";

/**
 * Reusable dark inquiry banner used on the home export preview, the
 * export page, and product detail pages (with overridable copy).
 */
export function ExportCTA({
  title,
  body,
  button,
  href = "/contact",
}: {
  title?: string;
  body?: string;
  button?: string;
  href?: string;
}) {
  const { t } = useLang();

  return (
    <div className="relative overflow-hidden rounded-3xl bg-espresso-900 p-8 text-cream-100 sm:p-10">
      <svg
        aria-hidden="true"
        viewBox="0 0 440 160"
        fill="none"
        className="pointer-events-none absolute -right-20 -top-14 w-96 opacity-20"
      >
        <path d="M4 130 C120 20 320 30 436 96" stroke="#E29A12" strokeWidth="10" strokeLinecap="round" />
      </svg>
      <div className="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div className="max-w-xl">
          <h3 className="font-display text-2xl font-semibold leading-snug">
            {title ?? t.exportSection.ctaTitle}
          </h3>
          <p className="mt-2 text-sm leading-relaxed text-cream-200/75">
            {body ?? t.exportSection.ctaBody}
          </p>
        </div>
        <Link href={href} className="btn-primary shrink-0 self-start lg:self-auto">
          {button ?? t.exportSection.ctaButton}
        </Link>
      </div>
    </div>
  );
}

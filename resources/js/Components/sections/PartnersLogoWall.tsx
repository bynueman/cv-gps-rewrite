import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import type { Partner } from "@/types";

/**
 * Admin-managed trust wall (Pengaturan → Mitra). Renders nothing when
 * there are no active partners yet, rather than showing an empty
 * section to visitors.
 */
export function PartnersLogoWall({ partners }: { partners: Partner[] }) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);

  if (partners.length === 0) {
    return null;
  }

  return (
    <section ref={scope} className="mx-auto max-w-6xl px-6 py-20 sm:px-14">
      <div className="text-center">
        <p data-reveal className="text-[13px] font-extrabold uppercase tracking-[0.08em] text-homeTerracotta">
          {t.partnersSection.kicker}
        </p>
        <h2 data-reveal className="mt-3 font-display text-2xl leading-tight text-homeInk sm:text-3xl">
          {t.partnersSection.title}
        </h2>
      </div>

      <div data-reveal className="mt-10 flex flex-wrap items-center justify-center gap-x-10 gap-y-8">
        {partners.map((partner) => (
          <span
            key={partner.name}
            className="flex h-14 w-32 items-center justify-center opacity-70 grayscale transition-all duration-300 hover:opacity-100 hover:grayscale-0"
          >
            {partner.logo ? (
              <img src={partner.logo} alt={partner.name} className="max-h-full max-w-full object-contain" />
            ) : (
              <span className="font-display text-sm font-semibold text-homeInk2">{partner.name}</span>
            )}
          </span>
        ))}
      </div>
    </section>
  );
}

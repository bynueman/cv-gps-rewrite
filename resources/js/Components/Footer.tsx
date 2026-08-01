import { Link, usePage } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { LangSwitch } from "@/Components/LangSwitch";
import type { SharedProps } from "@/types";

export function Footer() {
  const { lang, t } = useLang();
  const { company, brandAssets, brands } = usePage<SharedProps>().props.site;
  const year = new Date().getFullYear();

  const links = [
    { href: "/", label: t.nav.home },
    { href: "/products", label: t.nav.products },
    { href: "/export", label: t.nav.export },
    { href: "/news", label: t.nav.news },
    { href: "/contact", label: t.nav.contact },
  ];

  return (
    <footer className="bg-espresso-950 text-cream-200">
      <div className="container-page grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <div className="flex items-center gap-3">
            <span className="flex h-10 items-center rounded-xl bg-cream-50 px-2.5">
              <img
                src={brandAssets.logos.gps}
                alt={`Logo ${company.name}`}
                width={128}
                height={72}
                className="h-6 w-auto"
              />
            </span>
            <span className="font-display text-lg font-semibold text-cream-100">
              {company.name}
            </span>
          </div>
          <p className="mt-4 max-w-sm text-sm leading-relaxed text-cream-200/70">
            {t.footer.tagline}
          </p>
        </div>

        <nav aria-label="Footer">
          <h3 className="text-xs font-semibold uppercase tracking-[0.2em] text-gold-400">
            {t.footer.linksTitle}
          </h3>
          <ul className="mt-4 space-y-2.5">
            {links.map((l) => (
              <li key={l.href}>
                <Link href={l.href} className="text-sm text-cream-200/80 hover:text-cream-100">
                  {l.label}
                </Link>
              </li>
            ))}
          </ul>
        </nav>

        <nav aria-label="Brands">
          <h3 className="text-xs font-semibold uppercase tracking-[0.2em] text-gold-400">
            {t.footer.brandsTitle}
          </h3>
          <ul className="mt-4 space-y-2.5">
            {brands.map((b) => (
              <li key={b.key}>
                <Link href={b.href} className="text-sm text-cream-200/80 hover:text-cream-100">
                  {b.name}
                  <span className="block text-xs text-cream-200/50">{b.tag[lang]}</span>
                </Link>
              </li>
            ))}
          </ul>
        </nav>

        <div>
          <h3 className="text-xs font-semibold uppercase tracking-[0.2em] text-gold-400">
            {t.footer.contactTitle}
          </h3>
          <ul className="mt-4 space-y-2.5 text-sm text-cream-200/80">
            <li>{company.location[lang]}</li>
            <li>
              <a href={`mailto:${company.email}`} className="hover:text-cream-100">
                {company.email}
              </a>
            </li>
            {company.email_alt && (
              <li>
                <a href={`mailto:${company.email_alt}`} className="hover:text-cream-100">
                  {company.email_alt}
                </a>
              </li>
            )}
            <li>
              <a href={company.whatsapp_href} className="hover:text-cream-100">
                {company.whatsapp}
              </a>
            </li>
          </ul>
          <h3 className="mt-6 text-xs font-semibold uppercase tracking-[0.2em] text-gold-400">
            {t.footer.langTitle}
          </h3>
          <div className="mt-3">
            <LangSwitch tone="dark" />
          </div>
        </div>
      </div>

      {brandAssets.fostered_by.length > 0 && (
        <div className="border-t border-cream-100/10">
          <div className="container-page flex flex-col items-center gap-4 py-8 text-center">
            <span className="text-xs font-semibold uppercase tracking-[0.2em] text-gold-400">
              {t.footer.fosteredByTitle}
            </span>
            <div className="flex flex-wrap items-center justify-center gap-4">
              {brandAssets.fostered_by.map((f) => (
                <span
                  key={f.name}
                  className="flex h-14 items-center rounded-xl bg-cream-50 px-4"
                >
                  <img src={f.logo} alt={f.name} className="h-8 w-auto object-contain" />
                </span>
              ))}
            </div>
          </div>
        </div>
      )}

      <div className="border-t border-cream-100/10">
        <div className="container-page py-5 text-center text-xs text-cream-200/50">
          © {year} {company.name}. {t.footer.copyright}
        </div>
      </div>
    </footer>
  );
}

import { useEffect, useState } from "react";
import { Link, usePage } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { LangSwitch } from "@/Components/LangSwitch";
import type { SharedProps } from "@/types";

const links = [
  { href: "/", key: "home" },
  { href: "/products", key: "products" },
  { href: "/export", key: "export" },
  { href: "/news", key: "news" },
  { href: "/contact", key: "contact" },
] as const;

export function Header() {
  const { t } = useLang();
  const { url } = usePage<SharedProps>();
  const { company, brandAssets } = usePage<SharedProps>().props.site;
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 12);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  // close the mobile menu on navigation
  useEffect(() => setOpen(false), [url]);

  const isActive = (href: string) =>
    href === "/" ? url === "/" : url.startsWith(href);

  return (
    <header
      className={`fixed inset-x-0 top-0 z-50 transition-all duration-300 ${
        scrolled || open
          ? "border-b border-espresso-900/10 bg-cream-100/90 backdrop-blur-md"
          : "bg-transparent"
      }`}
    >
      <div className="container-page flex h-16 items-center justify-between gap-6 sm:h-20">
        {/* Logo area */}
        <Link href="/" className="flex items-center gap-3" aria-label={company.name}>
          <img
            src={brandAssets.logos.gps}
            alt={`Logo ${company.name}`}
            width={128}
            height={72}
            className="h-14 w-auto sm:h-16"
          />
          <span className="hidden font-sans text-base font-normal tracking-tight sm:inline">
            CV Gama Putra Santosa
          </span>
        </Link>

        <nav aria-label="Main" className="hidden items-center gap-7 lg:flex">
          {links.map((l) => (
            <Link
              key={l.href}
              href={l.href}
              aria-current={isActive(l.href) ? "page" : undefined}
              className={`text-sm font-medium transition-colors hover:text-espresso-950 ${
                isActive(l.href)
                  ? "text-espresso-950 underline decoration-gold-500 decoration-2 underline-offset-8"
                  : "text-espresso-700"
              }`}
            >
              {t.nav[l.key]}
            </Link>
          ))}
        </nav>

        <div className="flex items-center gap-3">
          <LangSwitch />
          <Link href="/contact" className="btn-primary hidden !px-5 !py-2 md:inline-flex">
            {t.nav.inquiry}
          </Link>
          {/* Mobile menu toggle */}
          <button
            type="button"
            onClick={() => setOpen((v) => !v)}
            aria-expanded={open}
            aria-controls="mobile-nav"
            aria-label="Menu"
            className="grid h-10 w-10 place-items-center rounded-full border border-espresso-900/20 lg:hidden"
          >
            <span className="relative block h-3 w-4">
              <span
                className={`absolute left-0 top-0 h-0.5 w-full bg-espresso-900 transition-transform ${open ? "top-1/2 -translate-y-1/2 rotate-45" : ""}`}
              />
              <span
                className={`absolute bottom-0 left-0 h-0.5 w-full bg-espresso-900 transition-transform ${open ? "bottom-1/2 translate-y-1/2 -rotate-45" : ""}`}
              />
            </span>
          </button>
        </div>
      </div>

      {open ? (
        <nav
          id="mobile-nav"
          aria-label="Mobile"
          className="border-t border-espresso-900/10 bg-cream-100/95 backdrop-blur-md lg:hidden"
        >
          <div className="container-page flex flex-col gap-1 py-4">
            {links.map((l) => (
              <Link
                key={l.href}
                href={l.href}
                aria-current={isActive(l.href) ? "page" : undefined}
                className={`rounded-lg px-3 py-2.5 text-sm font-medium hover:bg-cream-200 ${
                  isActive(l.href) ? "bg-cream-200 text-espresso-950" : "text-espresso-800"
                }`}
              >
                {t.nav[l.key]}
              </Link>
            ))}
          </div>
        </nav>
      ) : null}
    </header>
  );
}

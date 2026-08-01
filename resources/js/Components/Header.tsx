import { useEffect, useRef, useState } from "react";
import { Link, usePage } from "@inertiajs/react";
import { ChevronDown } from "lucide-react";
import { useLang } from "@/lib/i18n";
import { LangSwitch } from "@/Components/LangSwitch";
import type { SharedProps } from "@/types";

const links = [
  { href: "/", key: "home" },
  { href: "/products", key: "products" },
  { href: "/mitra", key: "partners" },
  {
    href: "/export",
    key: "export",
    children: [{ href: "/export/legalitas-sertifikasi", key: "exportLegality" }],
  },
  { href: "/news", key: "news" },
  { href: "/contact", key: "contact" },
] as const;

export function Header() {
  const { t } = useLang();
  const { url } = usePage<SharedProps>();
  const { company, brandAssets } = usePage<SharedProps>().props.site;
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);
  const [desktopSubmenuOpen, setDesktopSubmenuOpen] = useState(false);
  const [mobileSubmenuOpen, setMobileSubmenuOpen] = useState(false);
  const submenuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 12);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  // close the mobile menu and any open submenu on navigation
  useEffect(() => {
    setOpen(false);
    setDesktopSubmenuOpen(false);
    setMobileSubmenuOpen(false);
  }, [url]);

  // close the desktop submenu on outside click
  useEffect(() => {
    if (!desktopSubmenuOpen) return;
    const onClick = (e: MouseEvent) => {
      if (submenuRef.current && !submenuRef.current.contains(e.target as Node)) {
        setDesktopSubmenuOpen(false);
      }
    };
    document.addEventListener("mousedown", onClick);
    return () => document.removeEventListener("mousedown", onClick);
  }, [desktopSubmenuOpen]);

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
          {links.map((l) =>
            "children" in l && l.children ? (
              <div key={l.href} ref={submenuRef} className="relative">
                <button
                  type="button"
                  onClick={() => setDesktopSubmenuOpen((v) => !v)}
                  aria-expanded={desktopSubmenuOpen}
                  aria-haspopup="true"
                  className={`flex items-center gap-1 whitespace-nowrap border-0 bg-transparent p-0 text-sm font-medium transition-colors hover:text-espresso-950 ${
                    isActive(l.href)
                      ? "text-espresso-950 underline decoration-gold-500 decoration-2 underline-offset-8"
                      : "text-espresso-700"
                  }`}
                >
                  {t.nav[l.key]}
                  <ChevronDown
                    className={`h-3.5 w-3.5 transition-transform ${desktopSubmenuOpen ? "rotate-180" : ""}`}
                    aria-hidden="true"
                  />
                </button>
                {desktopSubmenuOpen ? (
                  <div className="absolute left-0 top-full mt-2 min-w-[230px] rounded-xl border border-espresso-900/10 bg-cream-50 p-1.5 shadow-lg">
                    <Link
                      href={l.href}
                      className={`block rounded-lg px-3 py-2 text-sm hover:bg-cream-200 hover:text-espresso-950 ${
                        url === l.href ? "text-espresso-950" : "text-espresso-700"
                      }`}
                    >
                      {t.nav[l.key]}
                    </Link>
                    {l.children.map((c) => (
                      <Link
                        key={c.href}
                        href={c.href}
                        className={`block rounded-lg px-3 py-2 text-sm hover:bg-cream-200 hover:text-espresso-950 ${
                          isActive(c.href) ? "text-espresso-950" : "text-espresso-700"
                        }`}
                      >
                        {t.nav[c.key]}
                      </Link>
                    ))}
                  </div>
                ) : null}
              </div>
            ) : (
              <Link
                key={l.href}
                href={l.href}
                aria-current={isActive(l.href) ? "page" : undefined}
                className={`whitespace-nowrap text-sm font-medium transition-colors hover:text-espresso-950 ${
                  isActive(l.href)
                    ? "text-espresso-950 underline decoration-gold-500 decoration-2 underline-offset-8"
                    : "text-espresso-700"
                }`}
              >
                {t.nav[l.key]}
              </Link>
            ),
          )}
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
            {links.map((l) =>
              "children" in l && l.children ? (
                <div key={l.href}>
                  <div className="flex items-center">
                    <Link
                      href={l.href}
                      aria-current={isActive(l.href) ? "page" : undefined}
                      className={`flex-1 rounded-lg px-3 py-2.5 text-sm font-medium hover:bg-cream-200 ${
                        isActive(l.href) ? "bg-cream-200 text-espresso-950" : "text-espresso-800"
                      }`}
                    >
                      {t.nav[l.key]}
                    </Link>
                    <button
                      type="button"
                      onClick={() => setMobileSubmenuOpen((v) => !v)}
                      aria-expanded={mobileSubmenuOpen}
                      aria-label="Toggle submenu"
                      className="grid h-9 w-9 place-items-center rounded-lg text-espresso-700 hover:bg-cream-200"
                    >
                      <ChevronDown
                        className={`h-4 w-4 transition-transform ${mobileSubmenuOpen ? "rotate-180" : ""}`}
                        aria-hidden="true"
                      />
                    </button>
                  </div>
                  {mobileSubmenuOpen ? (
                    <div className="ml-3 mt-1 flex flex-col gap-1 border-l border-espresso-900/10 pl-3">
                      {l.children.map((c) => (
                        <Link
                          key={c.href}
                          href={c.href}
                          aria-current={isActive(c.href) ? "page" : undefined}
                          className={`rounded-lg px-3 py-2 text-sm hover:bg-cream-200 ${
                            isActive(c.href) ? "bg-cream-200 text-espresso-950" : "text-espresso-700"
                          }`}
                        >
                          {t.nav[c.key]}
                        </Link>
                      ))}
                    </div>
                  ) : null}
                </div>
              ) : (
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
              ),
            )}
          </div>
        </nav>
      ) : null}
    </header>
  );
}

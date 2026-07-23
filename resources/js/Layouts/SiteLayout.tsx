import type { ReactNode } from "react";
import { LanguageProvider } from "@/lib/i18n";
import { Header } from "@/Components/Header";
import { Footer } from "@/Components/Footer";

/**
 * Public marketing site chrome — everything except /admin. Applied via
 * Inertia's persistent-layout pattern (Page.layout = ...), so Header/
 * Footer stay mounted across client-side navigations instead of
 * remounting per page.
 */
export default function SiteLayout({ children }: { children: ReactNode }) {
  return (
    <LanguageProvider>
      <Header />
      <main>{children}</main>
      <Footer />
    </LanguageProvider>
  );
}

import { Head, usePage } from "@inertiajs/react";
import type { ReactNode } from "react";
import { LanguageProvider, useLang } from "@/lib/i18n";
import { localized } from "@/lib/localized";
import { Header } from "@/Components/Header";
import { Footer } from "@/Components/Footer";
import type { SharedProps } from "@/types";

type Metadata = { title: string; description: string };

function PublicMetadata() {
  const { lang } = useLang();
  const { seo, seoEnglish, site } = usePage<SharedProps & { seo?: Metadata; seoEnglish?: Metadata }>().props;
  if (!seo || !site.metadata) return null;
  const selected = lang === "en" && seoEnglish ? seoEnglish : seo;
  const siteName = localized(site.metadata.title, lang, site.company.name);
  const originalSuffix = " — " + site.metadata.title.id;
  const fragment = selected.title.endsWith(originalSuffix) ? selected.title.slice(0, -originalSuffix.length) : selected.title;
  const title = fragment.endsWith(siteName) ? fragment : fragment + " — " + siteName;
  return <Head title={title}><meta name="description" content={selected.description || localized(site.metadata.description, lang)} /></Head>;
}

/** Persistent public layout keeps the selected language across Inertia navigation. */
export default function SiteLayout({ children }: { children: ReactNode }) {
  return (
    <LanguageProvider>
      <Header />
      <main>{children}</main>
      <Footer />
      <PublicMetadata />
    </LanguageProvider>
  );
}

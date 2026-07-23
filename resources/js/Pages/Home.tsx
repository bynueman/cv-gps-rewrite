import type { ReactElement } from "react";
import { Head } from "@inertiajs/react";
import { Hero } from "@/Components/sections/Hero";
import { CompanyIntro } from "@/Components/sections/CompanyIntro";
import { FeaturedProducts } from "@/Components/sections/FeaturedProducts";
import { ProductFamilies } from "@/Components/sections/ProductFamilies";
import { ExportPreview } from "@/Components/sections/ExportPreview";
import { NewsPreview } from "@/Components/sections/NewsPreview";
import { ContactCTA } from "@/Components/sections/ContactCTA";
import SiteLayout from "@/Layouts/SiteLayout";
import type { Article, Product } from "@/types";

type Seo = { title: string; description: string; canonical?: string };

function Home({
  seo,
  kuicipCount,
  tekoCount,
  featuredHero,
  featuredSupporting,
  familiesKuicip1,
  familiesKuicip2,
  familiesTeko1,
  familiesTeko2,
  featuredArticles,
}: {
  seo: Seo;
  kuicipCount: number;
  tekoCount: number;
  featuredHero: Product | null;
  featuredSupporting: Product[];
  familiesKuicip1: Product | null;
  familiesKuicip2: Product | null;
  familiesTeko1: Product | null;
  familiesTeko2: Product | null;
  featuredArticles: Article[];
}) {
  return (
    <>
      <Head title={seo.title}>
        <meta name="description" content={seo.description} />
        {seo.canonical ? <link rel="canonical" href={seo.canonical} /> : null}
      </Head>
      <div className="relative overflow-x-clip bg-homeBg text-homeInk">
        <Hero kuicipCount={kuicipCount} tekoCount={tekoCount} />
        <CompanyIntro />
        <FeaturedProducts hero={featuredHero} supporting={featuredSupporting} />
        <ProductFamilies
          kuicip1={familiesKuicip1}
          kuicip2={familiesKuicip2}
          teko1={familiesTeko1}
          teko2={familiesTeko2}
        />
        <ExportPreview />
        <NewsPreview articles={featuredArticles} />
        <ContactCTA />
      </div>
    </>
  );
}

Home.layout = (page: ReactElement) => <SiteLayout children={page} />;

export default Home;

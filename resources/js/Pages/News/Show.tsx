import type { ReactElement } from "react";
import { Link, Head } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import { ArticleCard, formatDate } from "@/Components/ArticleCard";
import SiteLayout from "@/Layouts/SiteLayout";
import type { Article } from "@/types";

type Seo = {
  title: string;
  description: string;
  canonical?: string;
  ogImage?: string | null;
  tags?: string[];
};

/**
 * Article detail template: large title area, clean reading column,
 * related posts. `body[0][lang]` is sanitized rich-text HTML from the
 * admin editor (server-side sanitization — see Phase 5), rendered here
 * via dangerouslySetInnerHTML through the shared .prose-article styles.
 */
function Show({ article, relatedArticles, seo }: { article: Article; relatedArticles: Article[]; seo: Seo }) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang, article.slug]);

  return (
    <>
      <Head title={seo.title}>
        <meta name="description" content={seo.description} />
        {seo.canonical ? <link rel="canonical" href={seo.canonical} /> : null}
      </Head>
      <article ref={scope} className="pb-20 pt-28 sm:pt-36 lg:pb-28">
        <div className="container-page">
          <nav data-reveal aria-label="Breadcrumb" className="text-sm font-medium text-espresso-500">
            <Link href="/news" className="hover:text-gold-700">
              ← {t.common.backToNews}
            </Link>
          </nav>

          <header className="mx-auto mt-8 max-w-3xl">
            <div data-reveal className="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.15em]">
              <span className="rounded-full bg-gold-500/15 px-3 py-1 text-gold-700">
                {article.category[lang]}
              </span>
              <time dateTime={article.date} className="text-espresso-500">
                {t.articleDetail.publishedLabel} {formatDate(article.date, lang)}
              </time>
            </div>
            <h1
              data-reveal
              className="mt-4 font-display text-3xl font-semibold leading-[1.15] tracking-tight sm:text-4xl lg:text-5xl"
            >
              {article.title[lang]}
            </h1>
            <p data-reveal className="mt-4 text-lg leading-relaxed text-espresso-600">
              {article.excerpt[lang]}
            </p>
          </header>

          <div
            data-reveal
            className="relative mx-auto mt-10 flex aspect-[16/7] max-w-4xl items-center justify-center overflow-hidden rounded-3xl bg-espresso-800"
          >
            {article.image ? (
              <img src={article.image} alt="" className="absolute inset-0 h-full w-full object-cover" />
            ) : (
              <span className="font-display text-6xl font-semibold text-cream-100/15">GPS</span>
            )}
          </div>

          <div
            data-reveal
            className="prose-article mx-auto mt-12 max-w-2xl"
            dangerouslySetInnerHTML={{ __html: article.body[0]?.[lang] ?? "" }}
          />

          {article.tags && article.tags.length > 0 ? (
            <div className="mx-auto mt-8 flex max-w-2xl flex-wrap gap-2">
              {article.tags.map((tag) => (
                <span
                  key={tag}
                  className="rounded-full bg-espresso-900/5 px-3 py-1 text-xs font-semibold text-espresso-600"
                >
                  #{tag}
                </span>
              ))}
            </div>
          ) : null}

          <div className="mt-20 border-t border-espresso-900/10 pt-12">
            <h2 data-reveal className="font-display text-2xl font-semibold">
              {t.common.relatedArticles}
            </h2>
            <div className="mt-8 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
              {relatedArticles.map((a) => (
                <div key={a.slug} data-reveal>
                  <ArticleCard article={a} />
                </div>
              ))}
            </div>
          </div>
        </div>
      </article>
    </>
  );
}

Show.layout = (page: ReactElement) => <SiteLayout children={page} />;

export default Show;

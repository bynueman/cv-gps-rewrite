import { Link } from "@inertiajs/react";
import { useLang, type Lang } from "@/lib/i18n";
import type { Article } from "@/types";

export function formatDate(iso: string, lang: Lang) {
  return new Date(iso).toLocaleDateString(lang === "id" ? "id-ID" : "en-GB", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

/**
 * Editorial article card — calmer motion language than product cards
 * on purpose (no lifts or accent blobs, just a quiet image zoom).
 */
export function ArticleCard({
  article,
  size = "default",
}: {
  article: Article;
  size?: "default" | "featured";
}) {
  const { lang, t } = useLang();
  const href = `/news/${article.slug}`;

  return (
    <article className="group">
      <Link href={href} className="block">
        <div
          className={`relative flex items-center justify-center overflow-hidden rounded-2xl bg-espresso-800 ${
            size === "featured" ? "aspect-[16/9]" : "aspect-[3/2]"
          }`}
        >
          {article.image ? (
            <img
              src={article.image}
              alt=""
              className="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
          ) : (
            <span className="font-display text-5xl font-semibold text-cream-100/15 transition-transform duration-500 group-hover:scale-105">
              GPS
            </span>
          )}
        </div>
        <div className="mt-4 flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.15em]">
          <span className="rounded-full bg-gold-500/15 px-3 py-1 text-gold-700">
            {article.category[lang]}
          </span>
          <time dateTime={article.date} className="text-espresso-500">
            {formatDate(article.date, lang)}
          </time>
        </div>
        <h3
          className={`mt-3 font-display font-semibold leading-snug transition-colors group-hover:text-gold-700 ${
            size === "featured" ? "text-2xl" : "text-lg"
          }`}
        >
          {article.title[lang]}
        </h3>
        <p className="mt-2 text-sm leading-relaxed text-espresso-600">{article.excerpt[lang]}</p>
        <span className="mt-3 inline-block text-sm font-semibold text-gold-600">
          {t.common.readMore} →
        </span>
      </Link>
    </article>
  );
}

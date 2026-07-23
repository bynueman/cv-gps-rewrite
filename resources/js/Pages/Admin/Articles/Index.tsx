import type { ReactElement } from "react";
import { Head, Link } from "@inertiajs/react";
import AdminLayout from "@/Layouts/AdminLayout";
import { DeleteArticleButton } from "@/Components/admin/DeleteArticleButton";
import { EmptyState } from "@/Components/admin/EmptyState";
import { EyeIcon, PencilIcon } from "@/Components/admin/icons";
import { Newspaper } from "lucide-react";

type ArticleListItem = {
  id: number;
  slug: string;
  title_id: string;
  category_label: string;
  date: string;
  image: string | null;
  image_thumb: string | null;
  featured: boolean;
  published: boolean;
};

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" });
}

function Index({ articles }: { articles: ArticleListItem[] }) {
  return (
    <>
      <Head title="Artikel" />
      <div className="flex items-center justify-between gap-4">
        <h1 className="font-display text-2xl font-semibold">Artikel</h1>
        <Link href={route("admin.articles.create")} className="btn-primary !px-4 !py-2 text-sm">
          + Tambah Blog
        </Link>
      </div>

      {articles.length === 0 ? (
        <div className="mt-8">
          <EmptyState
            icon={Newspaper}
            title="Belum ada artikel"
            description="Buat artikel pertama untuk ditampilkan di Berita & Kegiatan."
            actionLabel="+ Tambah Blog"
            actionHref={route("admin.articles.create")}
          />
        </div>
      ) : (
        <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {articles.map((a) => (
              <div key={a.id} className="overflow-hidden rounded-2xl border border-espresso-900/10 bg-cream-50 shadow-card">
                <div className="relative aspect-[16/10] bg-espresso-800">
                  {a.image_thumb || a.image ? (
                    <img
                      src={(a.image_thumb || a.image)!}
                      alt=""
                      className="absolute inset-0 h-full w-full object-cover"
                    />
                  ) : (
                    <span className="flex h-full items-center justify-center font-display text-3xl font-semibold text-cream-100/20">
                      GPS
                    </span>
                  )}
                  <span
                    className={`absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-semibold ${
                      a.published ? "bg-green-100 text-green-800" : "bg-cream-100 text-espresso-700"
                    }`}
                  >
                    {a.published ? "Published" : "Draft"}
                  </span>
                  {a.featured ? (
                    <span className="absolute right-3 top-3 rounded-full bg-gold-500/90 px-3 py-1 text-xs font-semibold text-espresso-950">
                      Featured
                    </span>
                  ) : null}
                </div>

                <div className="p-5">
                  <p className="text-xs font-semibold uppercase tracking-wide text-gold-700">
                    {a.category_label} · {formatDate(a.date)}
                  </p>
                  <h3 className="mt-2 line-clamp-2 font-display text-base font-semibold leading-snug text-espresso-950">
                    {a.title_id}
                  </h3>

                  <div className="mt-4 flex items-center justify-between border-t border-espresso-900/10 pt-4">
                    <div className="flex items-center gap-4">
                      <Link
                        href={route("admin.articles.edit", a.id)}
                        title="Edit"
                        className="inline-flex items-center gap-1.5 text-sm font-semibold text-espresso-900 hover:underline"
                      >
                        <PencilIcon className="h-4 w-4" />
                        Edit
                      </Link>
                      <DeleteArticleButton id={a.id} title={a.title_id} />
                    </div>
                    {a.published ? (
                      <a
                        href={`/news/${a.slug}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        title="Lihat di situs publik"
                        className="inline-flex items-center gap-1.5 text-sm font-semibold text-gold-700 hover:underline"
                      >
                        <EyeIcon className="h-4 w-4" />
                        Lihat
                      </a>
                    ) : null}
                  </div>
                </div>
              </div>
            ))}
        </div>
      )}
    </>
  );
}

Index.layout = (page: ReactElement) => <AdminLayout title="Artikel" children={page} />;

export default Index;

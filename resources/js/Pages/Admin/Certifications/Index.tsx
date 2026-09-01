import type { ReactElement } from "react";
import { Head, Link, router } from "@inertiajs/react";
import { ShieldCheck, FileText, ExternalLink } from "lucide-react";
import AdminLayout from "@/Layouts/AdminLayout";
import { DeleteButton } from "@/Components/admin/DeleteButton";
import { EmptyState } from "@/Components/admin/EmptyState";
import { PencilIcon } from "@/Components/admin/icons";

type Certification = {
  id: number;
  name: string;
  issuer: string | null;
  category: string;
  logo: string | null;
  pdf_url: string | null;
  valid_until: string | null;
  sort_order: number;
  is_active: boolean;
};

const CATEGORY_LABELS: Record<string, string> = {
  legal: "Legalitas Usaha",
  halal: "Halal",
  bpom: "BPOM",
  pirt: "P-IRT",
  other: "Lainnya",
};

const CATEGORY_COLORS: Record<string, string> = {
  legal: "bg-blue-100 text-blue-800",
  halal: "bg-green-100 text-green-800",
  bpom: "bg-orange-100 text-orange-800",
  pirt: "bg-purple-100 text-purple-800",
  other: "bg-cream-200 text-espresso-600",
};

const selectClass =
  "rounded-xl border border-espresso-900/15 bg-cream-50 px-3.5 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-gold-500";

function Index({
  certifications,
  filters,
}: {
  certifications: Certification[];
  filters: { category?: string };
}) {
  function applyFilter(category: string) {
    router.get(
      route("admin.certifications.index"),
      category ? { category } : {},
      { preserveState: true, replace: true },
    );
  }

  return (
    <>
      <Head title="Sertifikasi" />
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="font-display text-2xl font-semibold">Legalitas & Sertifikasi</h1>
        <Link href={route("admin.certifications.create")} className="btn-primary !px-4 !py-2 text-sm">
          + Tambah Sertifikasi
        </Link>
      </div>

      <div className="mt-6">
        <select
          className={selectClass}
          value={filters.category ?? ""}
          onChange={(e) => applyFilter(e.target.value)}
        >
          <option value="">Semua Kategori</option>
          {Object.entries(CATEGORY_LABELS).map(([value, label]) => (
            <option key={value} value={value}>
              {label}
            </option>
          ))}
        </select>
      </div>

      {certifications.length === 0 ? (
        <div className="mt-8">
          <EmptyState
            icon={ShieldCheck}
            title="Belum ada sertifikasi"
            description="Tambah dokumen legalitas atau sertifikasi produk untuk ditampilkan di halaman Ekspor."
            actionLabel="+ Tambah Sertifikasi"
            actionHref={route("admin.certifications.create")}
          />
        </div>
      ) : (
        <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {certifications.map((cert) => (
            <div
              key={cert.id}
              className="flex flex-col rounded-2xl border border-espresso-900/10 bg-cream-50 p-4"
            >
              {/* Logo + Info */}
              <div className="flex items-start gap-3">
                <span className="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white border border-espresso-900/10">
                  {cert.logo ? (
                    <img src={cert.logo} alt="" className="h-full w-full object-contain p-1.5" />
                  ) : (
                    <ShieldCheck className="h-6 w-6 text-espresso-400" />
                  )}
                </span>

                <div className="min-w-0 flex-1">
                  <p className="truncate font-semibold text-espresso-950">{cert.name}</p>
                  {cert.issuer ? (
                    <p className="truncate text-xs text-espresso-500">{cert.issuer}</p>
                  ) : null}
                </div>
              </div>

              {/* Badges row */}
              <div className="mt-3 flex flex-wrap items-center gap-2">
                <span
                  className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                    CATEGORY_COLORS[cert.category] ?? CATEGORY_COLORS.other
                  }`}
                >
                  {CATEGORY_LABELS[cert.category] ?? cert.category}
                </span>

                {cert.is_active ? (
                  <span className="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                    Aktif
                  </span>
                ) : (
                  <span className="rounded-full bg-cream-200 px-2.5 py-1 text-xs font-semibold text-espresso-600">
                    Nonaktif
                  </span>
                )}

                {cert.pdf_url ? (
                  <span className="inline-flex items-center gap-1 rounded-full bg-gold-500/20 px-2.5 py-1 text-xs font-semibold text-gold-700">
                    <FileText className="h-3 w-3" />
                    PDF
                  </span>
                ) : null}
              </div>

              {/* Valid until */}
              {cert.valid_until ? (
                <p className="mt-2 text-xs text-espresso-500">
                  Berlaku s/d:{" "}
                  <span className="font-medium text-espresso-700">
                    {new Date(cert.valid_until).toLocaleDateString("id-ID", {
                      day: "numeric",
                      month: "long",
                      year: "numeric",
                    })}
                  </span>
                </p>
              ) : null}

              {/* PDF preview link */}
              {cert.pdf_url ? (
                <a
                  href={cert.pdf_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="mt-2 inline-flex items-center gap-1 text-xs text-espresso-500 hover:text-espresso-900 hover:underline"
                >
                  <ExternalLink className="h-3 w-3" />
                  Buka PDF
                </a>
              ) : null}

              {/* Actions */}
              <div className="mt-auto flex items-center justify-end gap-4 border-t border-espresso-900/10 pt-3 mt-4">
                <Link
                  href={route("admin.certifications.edit", cert.id)}
                  className="inline-flex items-center gap-1.5 text-sm font-semibold text-espresso-900 hover:underline"
                >
                  <PencilIcon className="h-4 w-4" />
                  Edit
                </Link>
                <DeleteButton
                  itemLabel={`Sertifikasi "${cert.name}"`}
                  routeName="admin.certifications.destroy"
                  routeParams={cert.id}
                />
              </div>
            </div>
          ))}
        </div>
      )}
    </>
  );
}

Index.layout = (page: ReactElement) => <AdminLayout title="Legalitas & Sertifikasi" children={page} />;

export default Index;

import type { ReactElement } from "react";
import { Head, Link, router } from "@inertiajs/react";
import { Handshake } from "lucide-react";
import AdminLayout from "@/Layouts/AdminLayout";
import { DeleteButton } from "@/Components/admin/DeleteButton";
import { EmptyState } from "@/Components/admin/EmptyState";
import { PencilIcon } from "@/Components/admin/icons";

type Partner = {
  id: number;
  name: string;
  category: string;
  logo: string | null;
  sort_order: number;
  is_active: boolean;
  featured: boolean;
};

const CATEGORY_LABELS: Record<string, string> = {
  ritel: "Ritel",
  hotel: "Hotel",
  restaurant: "Restoran",
  "oleh-oleh": "Oleh-Oleh",
  cakery: "Cakery",
  institusi: "Institusi",
};

const selectClass =
  "rounded-xl border border-espresso-900/15 bg-cream-50 px-3.5 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-gold-500";

function Index({ partners, filters }: { partners: Partner[]; filters: { category?: string } }) {
  function applyFilter(category: string) {
    router.get(route("admin.partners.index"), category ? { category } : {}, { preserveState: true, replace: true });
  }

  return (
    <>
      <Head title="Mitra" />
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="font-display text-2xl font-semibold">Mitra</h1>
        <Link href={route("admin.partners.create")} className="btn-primary !px-4 !py-2 text-sm">
          + Tambah Mitra
        </Link>
      </div>

      <div className="mt-6">
        <select className={selectClass} value={filters.category ?? ""} onChange={(e) => applyFilter(e.target.value)}>
          <option value="">Semua Kategori</option>
          {Object.entries(CATEGORY_LABELS).map(([value, label]) => (
            <option key={value} value={value}>
              {label}
            </option>
          ))}
        </select>
      </div>

      {partners.length === 0 ? (
        <div className="mt-8">
          <EmptyState
            icon={Handshake}
            title="Belum ada mitra"
            description="Tambah mitra pertama untuk ditampilkan di logo wall situs."
            actionLabel="+ Tambah Mitra"
            actionHref={route("admin.partners.create")}
          />
        </div>
      ) : (
        <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {partners.map((p) => (
            <div key={p.id} className="rounded-2xl border border-espresso-900/10 bg-cream-50 p-4">
              <div className="flex items-center gap-3">
                <span className="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white">
                  {p.logo ? (
                    <img src={p.logo} alt="" className="h-full w-full object-contain p-1.5" />
                  ) : (
                    <Handshake className="h-5 w-5 text-espresso-400" />
                  )}
                </span>
                <div className="min-w-0 flex-1">
                  <p className="truncate font-semibold text-espresso-950">{p.name}</p>
                  <p className="text-xs text-espresso-500">{CATEGORY_LABELS[p.category] ?? p.category}</p>
                </div>
                <div className="flex shrink-0 flex-col items-end gap-1">
                  <span
                    className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                      p.is_active ? "bg-green-100 text-green-800" : "bg-cream-200 text-espresso-600"
                    }`}
                  >
                    {p.is_active ? "Aktif" : "Nonaktif"}
                  </span>
                  {p.featured ? (
                    <span className="rounded-full bg-gold-500/20 px-2.5 py-1 text-xs font-semibold text-gold-700">
                      Beranda
                    </span>
                  ) : null}
                </div>
              </div>
              <div className="mt-3 flex items-center justify-end gap-4 border-t border-espresso-900/10 pt-3">
                <Link
                  href={route("admin.partners.edit", p.id)}
                  className="inline-flex items-center gap-1.5 text-sm font-semibold text-espresso-900 hover:underline"
                >
                  <PencilIcon className="h-4 w-4" />
                  Edit
                </Link>
                <DeleteButton itemLabel={`Mitra "${p.name}"`} routeName="admin.partners.destroy" routeParams={p.id} />
              </div>
            </div>
          ))}
        </div>
      )}
    </>
  );
}

Index.layout = (page: ReactElement) => <AdminLayout title="Mitra" children={page} />;

export default Index;

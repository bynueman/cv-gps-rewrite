import { useState, type FormEvent, type ReactElement } from "react";
import { Head, Link, router } from "@inertiajs/react";
import { Package, Search } from "lucide-react";
import AdminLayout from "@/Layouts/AdminLayout";
import { DeleteButton } from "@/Components/admin/DeleteButton";
import { EmptyState } from "@/Components/admin/EmptyState";
import { PencilIcon } from "@/Components/admin/icons";

type Brand = { id: number; key: string; name: string };

type ProductRow = {
  id: number;
  slug: string;
  brand_key: string;
  brand_name: string;
  name_id: string;
  name_en: string;
  image: string | null;
  featured: boolean;
  is_active: boolean;
  sort_order: number;
};

const selectClass =
  "rounded-xl border border-espresso-900/15 bg-cream-50 px-3.5 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-gold-500";

function Index({
  products,
  brands,
  filters,
}: {
  products: ProductRow[];
  brands: Brand[];
  filters: { brand?: string; status?: string; q?: string };
}) {
  const [q, setQ] = useState(filters.q ?? "");

  function applyFilter(next: Partial<typeof filters>) {
    router.get(route("admin.products.index"), { ...filters, ...next }, { preserveState: true, replace: true });
  }

  function handleSearchSubmit(e: FormEvent) {
    e.preventDefault();
    applyFilter({ q });
  }

  return (
    <>
      <Head title="Produk" />
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="font-display text-2xl font-semibold">Produk</h1>
        <Link href={route("admin.products.create")} className="btn-primary !px-4 !py-2 text-sm">
          + Tambah Produk
        </Link>
      </div>

      <div className="mt-6 flex flex-wrap items-center gap-3">
        <select
          className={selectClass}
          value={filters.brand ?? ""}
          onChange={(e) => applyFilter({ brand: e.target.value || undefined })}
        >
          <option value="">Semua Brand</option>
          {brands.map((b) => (
            <option key={b.key} value={b.key}>
              {b.name}
            </option>
          ))}
        </select>

        <select
          className={selectClass}
          value={filters.status ?? ""}
          onChange={(e) => applyFilter({ status: e.target.value || undefined })}
        >
          <option value="">Semua Status</option>
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </select>

        <form onSubmit={handleSearchSubmit} className="flex items-center gap-2">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-espresso-500" />
            <input
              type="search"
              value={q}
              onChange={(e) => setQ(e.target.value)}
              placeholder="Cari nama produk…"
              className={`${selectClass} pl-9`}
            />
          </div>
        </form>
      </div>

      {products.length === 0 ? (
        <div className="mt-8">
          <EmptyState
            icon={Package}
            title="Belum ada produk"
            description="Tambah produk pertama untuk ditampilkan di katalog."
            actionLabel="+ Tambah Produk"
            actionHref={route("admin.products.create")}
          />
        </div>
      ) : (
        <>
          {/* Table — desktop */}
          <div className="mt-6 hidden overflow-hidden rounded-2xl border border-espresso-900/10 bg-cream-50 sm:block">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-espresso-900/10 bg-espresso-900/[0.03] text-xs uppercase tracking-wide text-espresso-500">
                <tr>
                  <th className="px-4 py-3 font-semibold">Produk</th>
                  <th className="px-4 py-3 font-semibold">Brand</th>
                  <th className="px-4 py-3 font-semibold">Status</th>
                  <th className="px-4 py-3 font-semibold">Urutan</th>
                  <th className="px-4 py-3 font-semibold text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {products.map((p) => (
                  <tr key={p.id} className="border-b border-espresso-900/5 last:border-0">
                    <td className="flex items-center gap-3 px-4 py-3">
                      <span className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-espresso-900/5">
                        {p.image ? (
                          <img src={p.image} alt="" className="h-full w-full object-cover" />
                        ) : (
                          <Package className="h-4 w-4 text-espresso-400" />
                        )}
                      </span>
                      <span>
                        <span className="block font-semibold text-espresso-950">{p.name_id}</span>
                        {p.featured ? (
                          <span className="text-xs font-semibold text-gold-700">Featured</span>
                        ) : null}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-espresso-600">{p.brand_name}</td>
                    <td className="px-4 py-3">
                      <span
                        className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                          p.is_active ? "bg-green-100 text-green-800" : "bg-cream-200 text-espresso-600"
                        }`}
                      >
                        {p.is_active ? "Aktif" : "Nonaktif"}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-espresso-600">{p.sort_order}</td>
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-end gap-4">
                        <Link
                          href={route("admin.products.edit", p.id)}
                          className="inline-flex items-center gap-1.5 text-sm font-semibold text-espresso-900 hover:underline"
                        >
                          <PencilIcon className="h-4 w-4" />
                          Edit
                        </Link>
                        <DeleteButton itemLabel={`Produk "${p.name_id}"`} routeName="admin.products.destroy" routeParams={p.id} />
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Cards — mobile */}
          <div className="mt-6 space-y-3 sm:hidden">
            {products.map((p) => (
              <div key={p.id} className="rounded-2xl border border-espresso-900/10 bg-cream-50 p-4">
                <div className="flex items-center gap-3">
                  <span className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-espresso-900/5">
                    {p.image ? (
                      <img src={p.image} alt="" className="h-full w-full object-cover" />
                    ) : (
                      <Package className="h-5 w-5 text-espresso-400" />
                    )}
                  </span>
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-semibold text-espresso-950">{p.name_id}</p>
                    <p className="text-xs text-espresso-500">{p.brand_name}</p>
                  </div>
                  <span
                    className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ${
                      p.is_active ? "bg-green-100 text-green-800" : "bg-cream-200 text-espresso-600"
                    }`}
                  >
                    {p.is_active ? "Aktif" : "Nonaktif"}
                  </span>
                </div>
                <div className="mt-3 flex items-center justify-end gap-4 border-t border-espresso-900/10 pt-3">
                  <Link
                    href={route("admin.products.edit", p.id)}
                    className="inline-flex items-center gap-1.5 text-sm font-semibold text-espresso-900 hover:underline"
                  >
                    <PencilIcon className="h-4 w-4" />
                    Edit
                  </Link>
                  <DeleteButton itemLabel={`Produk "${p.name_id}"`} routeName="admin.products.destroy" routeParams={p.id} />
                </div>
              </div>
            ))}
          </div>
        </>
      )}
    </>
  );
}

Index.layout = (page: ReactElement) => <AdminLayout title="Produk" children={page} />;

export default Index;

import type { FormEvent, ReactElement } from "react";
import { Head, Link, router } from "@inertiajs/react";
import { ArrowUpRight, Search } from "lucide-react";
import AdminLayout from "@/Layouts/AdminLayout";
import { Pagination, type PaginationData } from "@/Components/admin/Pagination";
import { storyStatusLabels, storyVisibilityLabels } from "@/lib/storyLabels";

type Row = { id: number; code: string; excerpt: string; visibility: string; status: string; redacted?: boolean };
type Filters = { status?: string; visibility?: string; reports?: boolean; code?: string };
function Index({ stories, pendingReports, filters }: { stories: PaginationData & { data: Row[] }; pendingReports: number; filters: Filters }) {
  const tabs = [
    { label: "Semua", suffix: "", active: !filters.status && !filters.visibility && !filters.reports },
    ...["pending", "approved", "rejected", "hidden"].map(status => ({ label: storyStatusLabels[status], suffix: "?status=" + status, active: filters.status === status })),
    { label: "Privat", suffix: "?visibility=private", active: filters.visibility === "private" },
    { label: "Laporan (" + pendingReports + ")", suffix: "?reports=1", active: !!filters.reports },
  ];
  function search(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const code = String(new FormData(event.currentTarget).get("code") ?? "").trim().toUpperCase();
    router.get(route("admin.stories.index"), { ...filters, code: code || undefined }, { preserveState: true, preserveScroll: true });
  }
  return <>
    <Head title="Kuicip — Ruang Cerita"/>
    <p className="kicker">Moderasi superadmin</p>
    <h1 className="mt-3 font-display text-3xl">Kuicip — Ruang Cerita</h1>
    <p className="mt-4 max-w-2xl text-sm leading-relaxed text-espresso-600">Tinjau data pribadi, persetujuan, dan konteks sebelum memublikasikan. Cerita privat tetap privat setelah disetujui; jangan salin ke layanan terjemahan atau media sosial.</p>
    <nav aria-label="Filter moderasi" className="my-6 flex flex-wrap gap-2">{tabs.map(tab => <Link key={tab.suffix} href={route("admin.stories.index") + tab.suffix} aria-current={tab.active ? "page" : undefined} className={`min-h-11 content-center rounded-full border px-4 py-2 text-sm font-semibold transition-colors ${tab.active ? "border-espresso-900 bg-espresso-900 text-cream-50" : "border-espresso-900/20 hover:bg-gold-300/25"}`}>{tab.label}</Link>)}</nav>
    <form onSubmit={search} className="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-espresso-900/10 bg-cream-50 p-4">
      <label className="min-w-0 flex-1 text-xs font-semibold">Cari kode cerita<input key={filters.code || ""} name="code" defaultValue={filters.code || ""} pattern="KC-[0-9]{4,20}" placeholder="KC-0006" className="mt-2 w-full rounded-xl border border-espresso-900/20 bg-cream-50 px-4 py-3 font-mono text-sm"/></label>
      <button className="btn-outline min-h-12"><Search size={16} aria-hidden="true"/>Cari</button>
      {filters.code && <Link href={route("admin.stories.index", { ...filters, code: undefined })} className="min-h-11 content-center px-2 text-sm underline underline-offset-4">Hapus pencarian</Link>}
      <p className="w-full text-xs leading-5 text-espresso-600">Gunakan kode lengkap. Pencarian mengikuti filter yang dipilih.</p>
    </form>
    <div className="space-y-3">{stories.data.map(story => <Link key={story.id} href={route("admin.stories.show", story.id)} className="group block rounded-2xl border border-espresso-900/10 bg-cream-50 p-5 transition-colors hover:border-gold-700/50 sm:p-6">
      <div className="flex flex-wrap items-center justify-between gap-3"><span className="font-mono text-sm font-semibold">{story.code}</span><div className="flex flex-wrap gap-2 text-xs"><span className="rounded-full border border-espresso-900/15 px-3 py-1">{storyVisibilityLabels[story.visibility] ?? story.visibility}</span><span className={`rounded-full px-3 py-1 font-semibold ${story.status === "pending" ? "bg-gold-300/30" : "bg-cream-200"}`}>{story.redacted ? "Diredaksi" : storyStatusLabels[story.status] ?? story.status}</span></div></div>
      <p className="my-4 line-clamp-3 whitespace-pre-wrap text-sm leading-relaxed text-espresso-700 [overflow-wrap:anywhere]">{story.excerpt}</p>
      <span className="inline-flex items-center gap-2 text-xs font-semibold">Tinjau cerita<ArrowUpRight size={15} aria-hidden="true"/></span>
    </Link>)}{!stories.data.length && <div role="status" className="rounded-2xl border border-dashed border-espresso-900/20 p-10 text-center"><p className="font-semibold">Tidak ada kiriman yang cocok.</p><p className="mt-2 text-sm text-espresso-600">Periksa kode lengkap atau pilih filter lain.</p></div>}</div>
    <Pagination page={stories}/>
  </>;
}
Index.layout = (page: ReactElement) => <AdminLayout title="Ruang Cerita">{page}</AdminLayout>;
export default Index;

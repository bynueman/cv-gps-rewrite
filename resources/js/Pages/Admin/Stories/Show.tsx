import { type FormEvent, type ReactElement } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import AdminLayout from "@/Layouts/AdminLayout";
import { Pagination, type PaginationData } from "@/Components/admin/Pagination";
import { CopyStoryCode } from "@/Components/CopyStoryCode";
import { StoryText } from "@/Components/StoryDetailModal";
import { storyCategoryName, storyStatusLabels, storyVisibilityLabels } from "@/lib/storyLabels";

type Story = { id: number; body: string; category: string; visibility: string; status: string; publication_consent: boolean; social_consent: boolean; policy_version: string };
type Event = { id: number; action: string; from_status: string | null; to_status: string; reason: string | null; actor_id: number | null; created_at: string };
type Report = { id: number; reason: string; details: string | null; status: string };
const field = "mt-2 w-full rounded-xl border border-espresso-900/20 bg-cream-50 p-3 text-sm";
function ReviewReport({ report }: { report: Report }) {
  const { data, setData, patch, processing, errors } = useForm({ status: "resolved", reason: "" });
  function submit(event: FormEvent) { event.preventDefault(); patch(route("admin.stories.reports.update", report.id), { preserveScroll: true }); }
  const reasons: Record<string, string> = { privacy: "Data pribadi", harmful: "Konten berbahaya", spam: "Spam", other: "Lainnya" };
  return <form onSubmit={submit} className="mt-4 rounded-2xl border border-espresso-900/15 bg-cream-50 p-5">
    <p className="flex flex-wrap justify-between gap-2 text-sm font-semibold"><span>{reasons[report.reason] ?? report.reason}</span><span className="text-xs text-espresso-600">{report.status === "pending" ? "Menunggu tinjauan" : report.status === "resolved" ? "Selesai ditinjau" : "Ditutup"}</span></p>
    {report.details && <p className="mt-3 whitespace-pre-wrap text-sm leading-relaxed [overflow-wrap:anywhere]">{report.details}</p>}
    {report.status === "pending" && <><label className="mt-4 block text-xs font-semibold">Catatan peninjauan<input required maxLength={300} value={data.reason} onChange={event => setData("reason", event.target.value)} className={field}/></label>{Object.values(errors).map((error, i) => <p role="alert" key={i} className="mt-2 text-sm text-red-700">{error}</p>)}<button disabled={processing} className="btn-outline mt-3 disabled:opacity-50">{processing ? "Menyimpan…" : "Tandai ditinjau"}</button></>}
  </form>;
}
function Show({ story, code, history, reports }: { story: Story; code: string; history: Event[]; reports: PaginationData & { data: Report[] } }) {
  const { data, setData, patch, processing, errors } = useForm({ status: "approved", reason: "" });
  const hide = useForm({ status: "hidden", reason: "Disembunyikan segera untuk peninjauan" });
  function submit(event: FormEvent) { event.preventDefault(); patch(route("admin.stories.update", story.id), { preserveScroll: true }); }
  return <>
    <Head title={code + " — Moderasi"}/>
    <Link href={route("admin.stories.index")} className="inline-block py-2 text-sm underline underline-offset-4">← Daftar cerita</Link>
    <div className="my-6 flex flex-wrap items-center justify-between gap-5">
      <div><p className="kicker">Tinjau cerita</p><div className="mt-3 flex flex-wrap items-center gap-4"><h1 className="font-display text-3xl">{code}</h1><CopyStoryCode code={code}/></div><div className="mt-3 flex flex-wrap gap-2 text-xs font-semibold"><span className="rounded-full border border-espresso-900/15 px-3 py-1">{storyVisibilityLabels[story.visibility] ?? story.visibility}</span><span className="rounded-full bg-gold-300/30 px-3 py-1">{storyStatusLabels[story.status] ?? story.status}</span></div></div>
      <button disabled={processing || hide.processing} className="btn-outline disabled:opacity-50" onClick={() => hide.patch(route("admin.stories.update", story.id), { preserveScroll: true })}>{hide.processing ? "Menyembunyikan…" : "Sembunyikan segera"}</button>
    </div>
    {Object.values(hide.errors).map((error, i) => <p role="alert" key={i} className="mb-4 rounded-xl bg-red-50 p-4 text-sm text-red-800">{error}</p>)}
    <div className="grid items-start gap-6 lg:grid-cols-[1.2fr_1fr]">
      <section className="min-w-0 rounded-3xl border border-espresso-900/15 bg-cream-50 p-5 sm:p-7">
        <span className="inline-block rounded-full bg-gold-300/30 px-3 py-1 text-xs font-semibold">{storyCategoryName(story.category)}</span>
        <div className="my-6"><StoryText body={story.body}/></div>
        <h2 className="border-t border-espresso-900/15 pt-5 text-sm font-semibold">Persetujuan & privasi</h2>
        <dl className="mt-4 space-y-3 text-sm">{[["Pembaca", storyVisibilityLabels[story.visibility] ?? story.visibility], ["Izin publikasi", story.publication_consent ? "Ya" : "Tidak"], ["Izin media sosial", story.social_consent ? "Ya" : "Tidak"], ["Versi panduan", story.policy_version]].map(([label, value]) => <div key={label} className="flex flex-wrap justify-between gap-x-4 gap-y-1"><dt className="text-espresso-600">{label}</dt><dd className="font-semibold [overflow-wrap:anywhere]">{value}</dd></div>)}</dl>
        {story.visibility === "private" && <p className="mt-5 rounded-xl bg-gold-300/20 p-4 text-sm leading-relaxed">Privat — persetujuan moderasi tidak memublikasikan kiriman ini. Jangan salin cerita privat ke layanan lain.</p>}
      </section>
      <section className="min-w-0">
        <form onSubmit={submit} className="rounded-3xl border border-espresso-900/15 bg-cream-50 p-5 sm:p-6" aria-busy={processing}>
          <h2 className="font-display text-xl">Keputusan moderasi</h2><p className="mt-2 text-xs leading-relaxed text-espresso-600">Periksa data pribadi, konteks, dan izin publikasi. Setiap keputusan dicatat beserta alasannya.</p>
          <label className="mt-5 block text-sm font-semibold">Status<select value={data.status} onChange={event => setData("status", event.target.value)} className={field}><option value="approved">Disetujui / selesai ditinjau</option><option value="rejected">Ditolak</option><option value="hidden">Disembunyikan</option></select></label>
          <label className="mt-4 block text-sm font-semibold">Alasan<textarea required maxLength={300} value={data.reason} onChange={event => setData("reason", event.target.value)} className={field} rows={3}/></label>
          {Object.values(errors).map((error, i) => <p role="alert" key={i} className="mt-2 text-sm text-red-700">{error}</p>)}
          <button disabled={processing || hide.processing} className="btn-primary mt-4 w-full disabled:opacity-50">{processing ? "Menyimpan…" : "Simpan keputusan"}</button>
        </form>
        <h2 className="mt-8 font-display text-xl">Laporan komunitas</h2>
        {reports.data.map(report => <ReviewReport key={report.id} report={report}/>)}{!reports.data.length && <p className="mt-3 rounded-2xl border border-dashed border-espresso-900/15 p-5 text-sm text-espresso-600">Belum ada laporan untuk cerita ini.</p>}
        <Pagination page={reports}/>
      </section>
    </div>
    <section className="mt-10"><h2 className="font-display text-2xl">Riwayat moderasi</h2><p className="mt-2 text-xs leading-relaxed text-espresso-600">Maksimal 50 kejadian terbaru. Isi cerita dan identitas pelapor tidak disalin ke log ini.</p>
      <ol className="mt-5 space-y-3">{history.map(event => <li key={event.id} className="rounded-xl border border-espresso-900/10 bg-cream-50 p-4 text-sm"><p className="font-semibold">{event.action}: {storyStatusLabels[event.from_status || ""] ?? event.from_status ?? "—"} → {storyStatusLabels[event.to_status] ?? event.to_status}</p><p className="mt-2 whitespace-pre-wrap leading-relaxed [overflow-wrap:anywhere]">{event.reason}</p><p className="mt-2 text-xs text-espresso-600">Staf #{event.actor_id || "—"} · {event.created_at}</p></li>)}</ol>
      {!history.length && <p className="mt-4 text-sm text-espresso-600">Belum ada keputusan moderasi.</p>}
    </section>
  </>;
}
Show.layout = (page: ReactElement) => <AdminLayout title="Moderasi cerita">{page}</AdminLayout>;
export default Show;

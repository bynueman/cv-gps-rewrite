import type { ReactElement } from "react";
import { Head, Link, router } from "@inertiajs/react";
import { Mail, MailOpen } from "lucide-react";
import AdminLayout from "@/Layouts/AdminLayout";
import { EmptyState } from "@/Components/admin/EmptyState";

type MessageRow = {
  id: number;
  name: string;
  email: string;
  topic: string;
  message: string;
  is_read: boolean;
  created_at: string;
};

const selectClass =
  "rounded-xl border border-espresso-900/15 bg-cream-50 px-3.5 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-gold-500";

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

function Index({ messages, filters }: { messages: MessageRow[]; filters: { status?: string } }) {
  function applyFilter(status: string) {
    router.get(route("admin.messages.index"), status ? { status } : {}, { preserveState: true, replace: true });
  }

  return (
    <>
      <Head title="Pesan Masuk" />
      <h1 className="font-display text-2xl font-semibold">Pesan Masuk</h1>

      <div className="mt-6">
        <select className={selectClass} value={filters.status ?? ""} onChange={(e) => applyFilter(e.target.value)}>
          <option value="">Semua Pesan</option>
          <option value="unread">Belum Dibaca</option>
          <option value="read">Sudah Dibaca</option>
        </select>
      </div>

      {messages.length === 0 ? (
        <div className="mt-8">
          <EmptyState icon={Mail} title="Belum ada pesan" description="Pesan dari form kontak situs akan muncul di sini." />
        </div>
      ) : (
        <div className="mt-6 overflow-hidden rounded-2xl border border-espresso-900/10 bg-cream-50">
          {messages.map((m, i) => (
            <Link
              key={m.id}
              href={route("admin.messages.show", m.id)}
              className={`flex items-start gap-4 px-4 py-4 hover:bg-espresso-900/[0.03] ${
                i > 0 ? "border-t border-espresso-900/10" : ""
              }`}
            >
              <span className="mt-0.5 shrink-0 text-espresso-500">
                {m.is_read ? <MailOpen className="h-5 w-5" /> : <Mail className="h-5 w-5 text-gold-600" />}
              </span>
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <p className={`truncate ${m.is_read ? "font-medium text-espresso-800" : "font-bold text-espresso-950"}`}>
                    {m.name} <span className="font-normal text-espresso-500">· {m.topic}</span>
                  </p>
                  <span className="shrink-0 text-xs text-espresso-500">{formatDate(m.created_at)}</span>
                </div>
                <p className="truncate text-sm text-espresso-600">{m.email}</p>
                <p className="mt-0.5 truncate text-sm text-espresso-500">{m.message}</p>
              </div>
            </Link>
          ))}
        </div>
      )}
    </>
  );
}

Index.layout = (page: ReactElement) => <AdminLayout title="Pesan Masuk" children={page} />;

export default Index;

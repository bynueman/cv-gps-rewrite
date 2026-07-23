import type { ReactElement } from "react";
import { Head, Link } from "@inertiajs/react";
import { Mail } from "lucide-react";
import AdminLayout from "@/Layouts/AdminLayout";
import { DeleteButton } from "@/Components/admin/DeleteButton";

type MessageDetail = {
  id: number;
  name: string;
  email: string;
  topic: string;
  message: string;
  created_at: string;
};

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString("id-ID", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function Show({ message }: { message: MessageDetail }) {
  const replySubject = encodeURIComponent(`Re: ${message.topic} — pesan Anda di gpsfood.id`);
  const mailtoHref = `mailto:${message.email}?subject=${replySubject}`;

  return (
    <>
      <Head title={`Pesan dari ${message.name}`} />
      <Link href={route("admin.messages.index")} className="text-sm font-semibold text-espresso-600 hover:text-gold-700">
        ← Kembali ke Pesan Masuk
      </Link>

      <div className="mt-4 max-w-2xl rounded-2xl border border-espresso-900/10 bg-cream-50 p-6 sm:p-8">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="font-display text-xl font-semibold">{message.name}</h1>
            <p className="mt-1 text-sm text-espresso-600">{message.email}</p>
          </div>
          <span className="rounded-full bg-gold-500/15 px-3 py-1 text-xs font-semibold text-gold-700">
            {message.topic}
          </span>
        </div>

        <p className="mt-2 text-xs text-espresso-500">{formatDate(message.created_at)}</p>

        <p className="mt-6 whitespace-pre-wrap text-sm leading-relaxed text-espresso-800">{message.message}</p>

        <div className="mt-8 flex flex-wrap items-center gap-4 border-t border-espresso-900/10 pt-6">
          <a href={mailtoHref} className="btn-primary inline-flex items-center gap-2 !px-4 !py-2 text-sm">
            <Mail className="h-4 w-4" />
            Balas via Email
          </a>
          <DeleteButton
            itemLabel={`Pesan dari "${message.name}"`}
            routeName="admin.messages.destroy"
            routeParams={message.id}
          />
        </div>
      </div>
    </>
  );
}

Show.layout = (page: ReactElement<{ message: MessageDetail }>) => (
  <AdminLayout title={`Pesan dari ${page.props.message.name}`} children={page} />
);

export default Show;

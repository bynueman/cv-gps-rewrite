import type { ReactElement } from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import AdminLayout from "@/Layouts/AdminLayout";
import { DeleteButton } from "@/Components/admin/DeleteButton";
import { PencilIcon } from "@/Components/admin/icons";
import type { SharedProps } from "@/types";

type UserRow = { id: number; name: string; email: string; role: "superadmin" | "editor" };

function Index({ users }: { users: UserRow[] }) {
  const { auth } = usePage<SharedProps>().props;
  const currentUserId = auth.user?.id;

  return (
    <>
      <Head title="Pengguna" />
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="font-display text-2xl font-semibold">Pengguna</h1>
        <Link href={route("admin.users.create")} className="btn-primary !px-4 !py-2 text-sm">
          + Tambah Pengguna
        </Link>
      </div>

      <div className="mt-6 overflow-hidden rounded-2xl border border-espresso-900/10 bg-cream-50">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-espresso-900/10 bg-espresso-900/[0.03] text-xs uppercase tracking-wide text-espresso-500">
            <tr>
              <th className="px-4 py-3 font-semibold">Nama</th>
              <th className="px-4 py-3 font-semibold">Email</th>
              <th className="px-4 py-3 font-semibold">Role</th>
              <th className="px-4 py-3 font-semibold text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {users.map((u) => (
              <tr key={u.id} className="border-b border-espresso-900/5 last:border-0">
                <td className="px-4 py-3 font-semibold text-espresso-950">
                  {u.name}
                  {u.id === currentUserId ? <span className="ml-2 text-xs font-normal text-espresso-500">(Anda)</span> : null}
                </td>
                <td className="px-4 py-3 text-espresso-600">{u.email}</td>
                <td className="px-4 py-3">
                  <span
                    className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                      u.role === "superadmin" ? "bg-gold-500/20 text-gold-700" : "bg-cream-200 text-espresso-600"
                    }`}
                  >
                    {u.role === "superadmin" ? "Superadmin" : "Editor"}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <div className="flex items-center justify-end gap-4">
                    <Link
                      href={route("admin.users.edit", u.id)}
                      className="inline-flex items-center gap-1.5 text-sm font-semibold text-espresso-900 hover:underline"
                    >
                      <PencilIcon className="h-4 w-4" />
                      Edit
                    </Link>
                    {u.id !== currentUserId ? (
                      <DeleteButton itemLabel={`Pengguna "${u.name}"`} routeName="admin.users.destroy" routeParams={u.id} />
                    ) : null}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}

Index.layout = (page: ReactElement) => <AdminLayout title="Pengguna" children={page} />;

export default Index;

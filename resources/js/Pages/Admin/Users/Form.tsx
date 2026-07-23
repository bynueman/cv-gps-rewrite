import type { FormEvent, ReactElement } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import AdminLayout from "@/Layouts/AdminLayout";
import { useUnsavedChangesGuard } from "@/hooks/useUnsavedChangesGuard";

const inputClass =
  "w-full rounded-xl border border-espresso-900/15 bg-cream-50 px-4 py-3 text-sm text-espresso-900 outline-none transition-colors focus:border-gold-500 focus:ring-2 focus:ring-gold-400/30";
const labelClass = "mb-1.5 block text-sm font-semibold";

type UserFormValues = { id: number; name: string; email: string; role: "superadmin" | "editor" };

function Form({ mode, user }: { mode: "create" | "edit"; user?: UserFormValues }) {
  const { data, setData, post, patch, processing, errors, isDirty } = useForm({
    name: user?.name ?? "",
    email: user?.email ?? "",
    role: user?.role ?? "editor",
    password: "",
    password_confirmation: "",
  });

  const { bypassNext } = useUnsavedChangesGuard(isDirty);

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    bypassNext();
    if (mode === "create") {
      post(route("admin.users.store"));
    } else {
      patch(route("admin.users.update", user!.id));
    }
  }

  return (
    <>
      <Head title={mode === "create" ? "Tambah Pengguna" : "Edit Pengguna"} />
      <h1 className="font-display text-2xl font-semibold">
        {mode === "create" ? "Tambah Pengguna" : "Edit Pengguna"}
      </h1>

      <form onSubmit={handleSubmit} className="mt-8 max-w-md space-y-5">
        <div>
          <label className={labelClass}>Nama</label>
          <input type="text" value={data.name} onChange={(e) => setData("name", e.target.value)} className={inputClass} />
          {errors.name ? <p className="mt-1.5 text-sm text-red-700">{errors.name}</p> : null}
        </div>

        <div>
          <label className={labelClass}>Email</label>
          <input
            type="email"
            value={data.email}
            onChange={(e) => setData("email", e.target.value)}
            className={inputClass}
          />
          {errors.email ? <p className="mt-1.5 text-sm text-red-700">{errors.email}</p> : null}
        </div>

        <div>
          <label className={labelClass}>Role</label>
          <select
            className={inputClass}
            value={data.role}
            onChange={(e) => setData("role", e.target.value as "superadmin" | "editor")}
          >
            <option value="editor">Editor</option>
            <option value="superadmin">Superadmin</option>
          </select>
          {errors.role ? <p className="mt-1.5 text-sm text-red-700">{errors.role}</p> : null}
          <p className="mt-1.5 text-xs text-espresso-500">
            Editor tidak bisa mengakses menu Pengguna & Pengaturan. Superadmin punya akses penuh.
          </p>
        </div>

        <div>
          <label className={labelClass}>{mode === "create" ? "Password" : "Password Baru (opsional)"}</label>
          <input
            type="password"
            autoComplete="new-password"
            value={data.password}
            onChange={(e) => setData("password", e.target.value)}
            className={inputClass}
          />
          {errors.password ? <p className="mt-1.5 text-sm text-red-700">{errors.password}</p> : null}
        </div>

        <div>
          <label className={labelClass}>Konfirmasi Password</label>
          <input
            type="password"
            autoComplete="new-password"
            value={data.password_confirmation}
            onChange={(e) => setData("password_confirmation", e.target.value)}
            className={inputClass}
          />
        </div>

        <div className="flex items-center gap-4 pt-2">
          <button type="submit" disabled={processing} className="btn-primary disabled:opacity-60">
            {processing ? "Menyimpan…" : "Simpan"}
          </button>
          <Link href={route("admin.users.index")} className="btn-outline">
            Batal
          </Link>
        </div>
      </form>
    </>
  );
}

Form.layout = (page: ReactElement<{ mode: "create" | "edit" }>) => (
  <AdminLayout title={page.props.mode === "create" ? "Tambah Pengguna" : "Edit Pengguna"} children={page} />
);

export default Form;

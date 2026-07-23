import type { FormEvent, ReactElement } from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import AdminLayout from "@/Layouts/AdminLayout";
import { useUnsavedChangesGuard } from "@/hooks/useUnsavedChangesGuard";
import type { SharedProps } from "@/types";

const inputClass =
  "mt-1.5 w-full rounded-xl border border-espresso-900/15 bg-cream-50 px-4 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-gold-500";
const labelClass = "text-xs font-semibold uppercase tracking-[0.1em] text-espresso-600";

function Profile() {
  const { auth } = usePage<SharedProps>().props;
  const user = auth.user as { name: string; email: string } | null;

  const { data, setData, patch, processing, errors, isDirty, reset } = useForm({
    name: user?.name ?? "",
    email: user?.email ?? "",
    current_password: "",
    password: "",
    password_confirmation: "",
  });

  const { bypassNext } = useUnsavedChangesGuard(isDirty);

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    bypassNext();
    patch(route("admin.profile.update"), {
      preserveScroll: true,
      onSuccess: () => reset("current_password", "password", "password_confirmation"),
    });
  }

  return (
    <>
      <Head title="Profil" />
      <h1 className="font-display text-2xl font-semibold">Profil Saya</h1>
      <p className="mt-2 text-sm text-espresso-600">Perbarui nama, email, atau password akun Anda.</p>

      <form onSubmit={handleSubmit} className="mt-8 max-w-md space-y-5">
        <div>
          <label htmlFor="name" className={labelClass}>
            Nama
          </label>
          <input
            id="name"
            type="text"
            required
            value={data.name}
            onChange={(e) => setData("name", e.target.value)}
            className={inputClass}
          />
          {errors.name ? <p className="mt-1.5 text-sm text-red-700">{errors.name}</p> : null}
        </div>

        <div>
          <label htmlFor="email" className={labelClass}>
            Email
          </label>
          <input
            id="email"
            type="email"
            required
            value={data.email}
            onChange={(e) => setData("email", e.target.value)}
            className={inputClass}
          />
          {errors.email ? <p className="mt-1.5 text-sm text-red-700">{errors.email}</p> : null}
        </div>

        <div className="border-t border-espresso-900/10 pt-5">
          <p className="text-sm font-semibold text-espresso-900">Ganti Password</p>
          <p className="mt-1 text-xs text-espresso-500">Kosongkan bagian ini jika tidak ingin mengganti password.</p>
        </div>

        <div>
          <label htmlFor="current_password" className={labelClass}>
            Password Saat Ini
          </label>
          <input
            id="current_password"
            type="password"
            autoComplete="current-password"
            value={data.current_password}
            onChange={(e) => setData("current_password", e.target.value)}
            className={inputClass}
          />
          {errors.current_password ? <p className="mt-1.5 text-sm text-red-700">{errors.current_password}</p> : null}
        </div>

        <div>
          <label htmlFor="password" className={labelClass}>
            Password Baru
          </label>
          <input
            id="password"
            type="password"
            autoComplete="new-password"
            value={data.password}
            onChange={(e) => setData("password", e.target.value)}
            className={inputClass}
          />
          {errors.password ? <p className="mt-1.5 text-sm text-red-700">{errors.password}</p> : null}
        </div>

        <div>
          <label htmlFor="password_confirmation" className={labelClass}>
            Konfirmasi Password Baru
          </label>
          <input
            id="password_confirmation"
            type="password"
            autoComplete="new-password"
            value={data.password_confirmation}
            onChange={(e) => setData("password_confirmation", e.target.value)}
            className={inputClass}
          />
        </div>

        <button type="submit" disabled={processing} className="btn-primary disabled:opacity-60">
          {processing ? "Menyimpan…" : "Simpan Perubahan"}
        </button>
      </form>
    </>
  );
}

Profile.layout = (page: ReactElement) => <AdminLayout title="Profil" children={page} />;

export default Profile;

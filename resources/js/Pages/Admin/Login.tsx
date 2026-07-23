import type { FormEvent, ReactElement } from "react";
import { Head, useForm } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";

function Login() {
  const { data, setData, post, processing, errors } = useForm({
    email: "",
    password: "",
  });

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    post(route("admin.login"), {
      onError: () => setData("password", ""),
    });
  }

  return (
    <>
      <Head title="Admin Login" />
      <div className="flex min-h-screen items-center justify-center px-5">
        <div className="w-full max-w-sm rounded-3xl border border-espresso-900/10 bg-cream-50 p-8 shadow-card">
          <h1 className="font-display text-2xl font-semibold text-espresso-950">Admin Login</h1>
          <p className="mt-1 text-sm text-espresso-600">CV Gama Putra Santosa</p>

          <form onSubmit={handleSubmit} className="mt-6 space-y-4">
            <div>
              <label htmlFor="email" className="text-xs font-semibold uppercase tracking-[0.1em] text-espresso-600">
                Email
              </label>
              <input
                id="email"
                type="email"
                required
                autoComplete="username"
                value={data.email}
                onChange={(e) => setData("email", e.target.value)}
                className="mt-1.5 w-full rounded-xl border border-espresso-900/15 bg-cream-100 px-4 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-gold-500"
              />
            </div>
            <div>
              <label
                htmlFor="password"
                className="text-xs font-semibold uppercase tracking-[0.1em] text-espresso-600"
              >
                Password
              </label>
              <input
                id="password"
                type="password"
                required
                autoComplete="current-password"
                value={data.password}
                onChange={(e) => setData("password", e.target.value)}
                className="mt-1.5 w-full rounded-xl border border-espresso-900/15 bg-cream-100 px-4 py-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-gold-500"
              />
            </div>

            {errors.email ? <p className="text-sm text-red-700">{errors.email}</p> : null}

            <button type="submit" disabled={processing} className="btn-primary w-full disabled:opacity-60">
              {processing ? "Memproses…" : "Masuk"}
            </button>
          </form>
        </div>
      </div>
    </>
  );
}

Login.layout = (page: ReactElement) => <GuestLayout children={page} />;

export default Login;

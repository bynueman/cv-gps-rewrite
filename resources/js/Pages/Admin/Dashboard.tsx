import type { ReactElement } from "react";
import { Head, usePage } from "@inertiajs/react";
import AdminLayout from "@/Layouts/AdminLayout";
import type { SharedProps } from "@/types";

/** Stat cards, activity feed, and visitor charts land here in Fase 6. */
function Dashboard() {
  const { auth } = usePage<SharedProps>().props;
  const user = auth.user as { name: string } | null;

  return (
    <>
      <Head title="Dashboard" />
      <h1 className="font-display text-2xl font-semibold">Selamat datang, {user?.name}.</h1>
      <p className="mt-2 max-w-xl text-sm leading-relaxed text-espresso-600">
        Ringkasan statistik (produk, artikel, mitra, pesan masuk, dan grafik pengunjung) akan tampil di
        sini pada tahap berikutnya. Untuk sekarang, gunakan menu di samping untuk mengelola konten.
      </p>
    </>
  );
}

Dashboard.layout = (page: ReactElement) => <AdminLayout title="Dashboard" children={page} />;

export default Dashboard;

import type { ReactElement } from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import {
  Area,
  AreaChart,
  CartesianGrid,
  Cell,
  Legend,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Package, Newspaper, Handshake, Mail } from "lucide-react";
import AdminLayout from "@/Layouts/AdminLayout";
import type { SharedProps } from "@/types";

type Stat = { active: number; total: number };
type ActivityItem = {
  id: number;
  user: string;
  action: string;
  module: string;
  item_label: string;
  created_at: string;
};
type VisitorPoint = { date: string; hits: number; uniques: number };
type TopPage = { path: string; hits: number };

const DONUT_COLORS = ["#E29A12", "#9C6508", "#C06B2D", "#5F7A3D", "#8A4A26", "#5E3B1E"];

const MODULE_LABELS: Record<string, string> = {
  products: "Produk",
  articles: "Artikel",
  partners: "Mitra",
  messages: "Pesan Masuk",
  users: "Pengguna",
  settings: "Pengaturan",
};

const ACTION_LABELS: Record<string, string> = {
  created: "menambahkan",
  updated: "memperbarui",
  deleted: "menghapus",
};

function formatDateTime(iso: string) {
  return new Date(iso).toLocaleDateString("id-ID", {
    day: "numeric",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function StatCard({
  icon: Icon,
  label,
  stat,
  href,
}: {
  icon: typeof Package;
  label: string;
  stat: Stat;
  href: string;
}) {
  return (
    <Link
      href={href}
      className="rounded-2xl border border-espresso-900/10 bg-cream-50 p-5 transition-colors hover:border-gold-500/40"
    >
      <div className="flex items-center justify-between">
        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-gold-500/15 text-gold-700">
          <Icon className="h-5 w-5" />
        </span>
        <span className="text-xs font-semibold text-espresso-500">{label}</span>
      </div>
      <p className="mt-4 font-display text-2xl font-semibold text-espresso-950">
        {stat.active}
        <span className="text-base font-normal text-espresso-500"> / {stat.total}</span>
      </p>
    </Link>
  );
}

function Dashboard({
  stats,
  recentActivity,
  visitorChart,
  browserBreakdown,
  deviceBreakdown,
  topPages,
}: {
  stats: { products: Stat; articles: Stat; partners: Stat; messages: Stat };
  recentActivity: ActivityItem[];
  visitorChart: VisitorPoint[];
  browserBreakdown: Record<string, number>;
  deviceBreakdown: Record<string, number>;
  topPages: TopPage[];
}) {
  const { auth } = usePage<SharedProps>().props;
  const user = auth.user as { name: string } | null;

  const browserData = Object.entries(browserBreakdown).map(([name, value]) => ({ name, value }));
  const deviceLabels: Record<string, string> = { mobile: "Mobile", desktop: "Desktop", tablet: "Tablet" };
  const deviceData = Object.entries(deviceBreakdown).map(([name, value]) => ({
    name: deviceLabels[name] ?? name,
    value,
  }));

  return (
    <>
      <Head title="Dashboard" />
      <h1 className="font-display text-2xl font-semibold">Selamat datang, {user?.name}.</h1>

      <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard icon={Package} label="Produk" stat={stats.products} href={route("admin.products.index")} />
        <StatCard icon={Newspaper} label="Artikel" stat={stats.articles} href={route("admin.articles.index")} />
        <StatCard icon={Handshake} label="Mitra" stat={stats.partners} href={route("admin.partners.index")} />
        <StatCard icon={Mail} label="Pesan Masuk" stat={stats.messages} href={route("admin.messages.index")} />
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-3">
        <div className="rounded-2xl border border-espresso-900/10 bg-cream-50 p-5 lg:col-span-2">
          <h2 className="font-display text-base font-semibold">Pengunjung 30 Hari Terakhir</h2>
          <div className="mt-4 h-64">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={visitorChart}>
                <defs>
                  <linearGradient id="hitsGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#E29A12" stopOpacity={0.35} />
                    <stop offset="95%" stopColor="#E29A12" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#2C1F1420" />
                <XAxis
                  dataKey="date"
                  tickFormatter={(d) => new Date(d).toLocaleDateString("id-ID", { day: "numeric", month: "short" })}
                  tick={{ fontSize: 11 }}
                  interval={Math.ceil(visitorChart.length / 8)}
                />
                <YAxis allowDecimals={false} tick={{ fontSize: 11 }} width={30} />
                <Tooltip
                  labelFormatter={(d) =>
                    typeof d === "string" || typeof d === "number"
                      ? new Date(d).toLocaleDateString("id-ID", { day: "numeric", month: "long" })
                      : ""
                  }
                />
                <Area type="monotone" dataKey="hits" name="Kunjungan" stroke="#E29A12" fill="url(#hitsGradient)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="rounded-2xl border border-espresso-900/10 bg-cream-50 p-5">
          <h2 className="font-display text-base font-semibold">Aktivitas Terakhir</h2>
          {recentActivity.length === 0 ? (
            <p className="mt-4 text-sm text-espresso-500">Belum ada aktivitas.</p>
          ) : (
            <ul className="mt-4 space-y-3">
              {recentActivity.map((item) => (
                <li key={item.id} className="text-sm leading-relaxed">
                  <span className="font-semibold text-espresso-900">{item.user}</span>{" "}
                  <span className="text-espresso-600">
                    {ACTION_LABELS[item.action] ?? item.action} {MODULE_LABELS[item.module] ?? item.module}{" "}
                    <span className="font-medium text-espresso-800">&ldquo;{item.item_label}&rdquo;</span>
                  </span>
                  <p className="text-xs text-espresso-500">{formatDateTime(item.created_at)}</p>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>

      <div className="mt-6 grid gap-6 lg:grid-cols-3">
        <div className="rounded-2xl border border-espresso-900/10 bg-cream-50 p-5">
          <h2 className="font-display text-base font-semibold">Browser</h2>
          {browserData.length === 0 ? (
            <p className="mt-4 text-sm text-espresso-500">Belum ada data.</p>
          ) : (
            <div className="mt-2 h-56">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie data={browserData} dataKey="value" nameKey="name" innerRadius={45} outerRadius={70} paddingAngle={2}>
                    {browserData.map((_, i) => (
                      <Cell key={i} fill={DONUT_COLORS[i % DONUT_COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip />
                  <Legend verticalAlign="bottom" height={24} iconSize={8} wrapperStyle={{ fontSize: 12 }} />
                </PieChart>
              </ResponsiveContainer>
            </div>
          )}
        </div>

        <div className="rounded-2xl border border-espresso-900/10 bg-cream-50 p-5">
          <h2 className="font-display text-base font-semibold">Perangkat</h2>
          {deviceData.length === 0 ? (
            <p className="mt-4 text-sm text-espresso-500">Belum ada data.</p>
          ) : (
            <div className="mt-2 h-56">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie data={deviceData} dataKey="value" nameKey="name" innerRadius={45} outerRadius={70} paddingAngle={2}>
                    {deviceData.map((_, i) => (
                      <Cell key={i} fill={DONUT_COLORS[i % DONUT_COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip />
                  <Legend verticalAlign="bottom" height={24} iconSize={8} wrapperStyle={{ fontSize: 12 }} />
                </PieChart>
              </ResponsiveContainer>
            </div>
          )}
        </div>

        <div className="rounded-2xl border border-espresso-900/10 bg-cream-50 p-5">
          <h2 className="font-display text-base font-semibold">Halaman Teratas</h2>
          {topPages.length === 0 ? (
            <p className="mt-4 text-sm text-espresso-500">Belum ada data.</p>
          ) : (
            <ul className="mt-4 space-y-2.5">
              {topPages.map((page) => (
                <li key={page.path} className="flex items-center justify-between text-sm">
                  <span className="truncate text-espresso-800">{page.path}</span>
                  <span className="shrink-0 font-semibold text-espresso-950">{page.hits}</span>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </>
  );
}

Dashboard.layout = (page: ReactElement) => <AdminLayout title="Dashboard" children={page} />;

export default Dashboard;

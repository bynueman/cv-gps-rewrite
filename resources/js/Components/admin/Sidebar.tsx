import { useEffect, useState } from "react";
import { Link, usePage } from "@inertiajs/react";
import {
  LayoutDashboard,
  Package,
  Newspaper,
  Handshake,
  Mail,
  Users,
  Settings,
  ChevronsLeft,
  ChevronsRight,
  X,
} from "lucide-react";
import type { SharedProps } from "@/types";

const NAV_ITEMS = [
  { routeName: "admin.dashboard", pathPrefix: "/admin", exact: true, label: "Dashboard", icon: LayoutDashboard },
  { routeName: "admin.products.index", pathPrefix: "/admin/products", label: "Produk", icon: Package },
  { routeName: "admin.articles.index", pathPrefix: "/admin/articles", label: "Artikel", icon: Newspaper },
  { routeName: "admin.partners.index", pathPrefix: "/admin/partners", label: "Mitra", icon: Handshake },
  {
    routeName: "admin.messages.index",
    pathPrefix: "/admin/messages",
    label: "Pesan Masuk",
    icon: Mail,
    badgeKey: "unreadMessagesCount" as const,
  },
  {
    routeName: "admin.users.index",
    pathPrefix: "/admin/users",
    label: "Pengguna",
    icon: Users,
    superadminOnly: true,
  },
  {
    routeName: "admin.settings.index",
    pathPrefix: "/admin/settings",
    label: "Pengaturan",
    icon: Settings,
    superadminOnly: true,
  },
];

const COLLAPSE_STORAGE_KEY = "admin.sidebar.collapsed";

function NavLink({
  item,
  collapsed,
  onNavigate,
}: {
  item: (typeof NAV_ITEMS)[number];
  collapsed: boolean;
  onNavigate?: () => void;
}) {
  const page = usePage<SharedProps & Record<string, unknown>>();
  const currentPath = page.url.split("?")[0];
  const isActive = item.exact ? currentPath === item.pathPrefix : currentPath.startsWith(item.pathPrefix);
  const Icon = item.icon;
  const badgeCount = item.badgeKey ? (page.props[item.badgeKey] as number | undefined) : undefined;

  return (
    <Link
      href={route(item.routeName)}
      onClick={onNavigate}
      title={collapsed ? item.label : undefined}
      className={`group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors ${
        isActive ? "bg-cream-100/10 text-cream-50" : "text-cream-200/70 hover:bg-cream-100/5 hover:text-cream-50"
      }`}
    >
      <Icon className="h-5 w-5 shrink-0" aria-hidden="true" />
      {!collapsed ? <span className="truncate">{item.label}</span> : null}
      {badgeCount ? (
        <span
          className={`flex h-5 min-w-5 items-center justify-center rounded-full bg-gold-500 px-1.5 text-[11px] font-bold text-espresso-950 ${
            collapsed ? "absolute -right-1 -top-1" : "ml-auto"
          }`}
        >
          {badgeCount > 99 ? "99+" : badgeCount}
        </span>
      ) : null}
    </Link>
  );
}

function SidebarContent({
  collapsed,
  onToggleCollapse,
  onNavigate,
  showCollapseToggle,
}: {
  collapsed: boolean;
  onToggleCollapse?: () => void;
  onNavigate?: () => void;
  showCollapseToggle: boolean;
}) {
  const { auth } = usePage<SharedProps>().props;
  const isSuperadmin = (auth.user as { role?: string } | null)?.role === "superadmin";

  return (
    <div className="flex h-full flex-col gap-1 p-3">
      <div className={`flex items-center gap-2 px-2 py-3 ${collapsed ? "justify-center" : ""}`}>
        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cream-50 font-display text-sm font-bold text-espresso-900">
          GPS
        </span>
        {!collapsed ? <span className="font-display text-sm font-semibold text-cream-50">Admin GPS</span> : null}
      </div>

      <nav className="mt-2 flex flex-1 flex-col gap-1" aria-label="Navigasi admin">
        {NAV_ITEMS.filter((item) => !item.superadminOnly || isSuperadmin).map((item) => (
          <NavLink key={item.routeName} item={item} collapsed={collapsed} onNavigate={onNavigate} />
        ))}
      </nav>

      {showCollapseToggle ? (
        <button
          type="button"
          onClick={onToggleCollapse}
          className="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-cream-200/60 hover:bg-cream-100/5 hover:text-cream-50"
        >
          {collapsed ? (
            <ChevronsRight className="h-5 w-5" aria-hidden="true" />
          ) : (
            <>
              <ChevronsLeft className="h-5 w-5" aria-hidden="true" />
              <span>Ciutkan</span>
            </>
          )}
        </button>
      ) : null}
    </div>
  );
}

export function Sidebar({
  mobileOpen,
  onCloseMobile,
  collapsed,
  onToggleCollapse,
}: {
  mobileOpen: boolean;
  onCloseMobile: () => void;
  collapsed: boolean;
  onToggleCollapse: () => void;
}) {
  return (
    <>
      {/* Desktop */}
      <aside
        className={`sticky top-0 hidden h-screen shrink-0 bg-espresso-950 transition-[width] duration-200 lg:block ${
          collapsed ? "w-[76px]" : "w-64"
        }`}
      >
        <SidebarContent collapsed={collapsed} onToggleCollapse={onToggleCollapse} showCollapseToggle />
      </aside>

      {/* Mobile drawer */}
      {mobileOpen ? (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div className="absolute inset-0 bg-espresso-950/60" onClick={onCloseMobile} aria-hidden="true" />
          <div className="absolute inset-y-0 left-0 w-72 bg-espresso-950 shadow-lift">
            <div className="flex justify-end p-3">
              <button
                type="button"
                onClick={onCloseMobile}
                aria-label="Tutup menu"
                className="rounded-lg p-1.5 text-cream-200/70 hover:bg-cream-100/5 hover:text-cream-50"
              >
                <X className="h-5 w-5" />
              </button>
            </div>
            <SidebarContent collapsed={false} onNavigate={onCloseMobile} showCollapseToggle={false} />
          </div>
        </div>
      ) : null}
    </>
  );
}

export function useSidebarCollapsed() {
  const [collapsed, setCollapsed] = useState(false);

  useEffect(() => {
    const stored = window.localStorage.getItem(COLLAPSE_STORAGE_KEY);
    if (stored === "1") setCollapsed(true);
  }, []);

  const toggle = () => {
    setCollapsed((prev) => {
      const next = !prev;
      window.localStorage.setItem(COLLAPSE_STORAGE_KEY, next ? "1" : "0");
      return next;
    });
  };

  return { collapsed, toggle };
}

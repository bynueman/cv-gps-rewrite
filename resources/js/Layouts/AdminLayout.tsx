import { useEffect, useState, type ReactNode } from "react";
import { usePage } from "@inertiajs/react";
import { Sidebar, useSidebarCollapsed } from "@/Components/admin/Sidebar";
import { Topbar } from "@/Components/admin/Topbar";
import { ToastProvider, useToast } from "@/Components/admin/Toast";
import type { SharedProps } from "@/types";

type FlashProps = { flash?: { status?: string | null; error?: string | null } };

/** Watches the shared `flash` props and turns a fresh one into a toast. */
function FlashWatcher() {
  const { flash } = usePage<SharedProps & FlashProps>().props;
  const toast = useToast();

  useEffect(() => {
    if (flash?.status) toast.success(flash.status);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [flash?.status]);

  useEffect(() => {
    if (flash?.error) toast.error(flash.error);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [flash?.error]);

  return null;
}

function AdminChrome({ title, children }: { title?: string; children: ReactNode }) {
  const { collapsed, toggle } = useSidebarCollapsed();
  const [mobileOpen, setMobileOpen] = useState(false);

  return (
    <div className="flex min-h-screen bg-cream-100 text-espresso-900">
      <Sidebar
        mobileOpen={mobileOpen}
        onCloseMobile={() => setMobileOpen(false)}
        collapsed={collapsed}
        onToggleCollapse={toggle}
      />
      <div className="flex min-w-0 flex-1 flex-col">
        <Topbar title={title} onOpenMobileSidebar={() => setMobileOpen(true)} />
        <main className="container-page flex-1 py-8">{children}</main>
      </div>
    </div>
  );
}

/** Admin chrome — no LanguageProvider (admin stays outside the bilingual UI). */
export default function AdminLayout({ title, children }: { title?: string; children: ReactNode }) {
  return (
    <ToastProvider>
      <FlashWatcher />
      <AdminChrome title={title}>{children}</AdminChrome>
    </ToastProvider>
  );
}

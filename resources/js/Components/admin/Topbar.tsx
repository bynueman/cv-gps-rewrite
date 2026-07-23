import { Fragment } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import { Menu, MenuButton, MenuItem, MenuItems, Transition } from "@headlessui/react";
import { Menu as MenuIcon, ExternalLink, ChevronDown, KeyRound, LogOut } from "lucide-react";
import type { SharedProps } from "@/types";

export function Topbar({ title, onOpenMobileSidebar }: { title?: string; onOpenMobileSidebar: () => void }) {
  const { auth } = usePage<SharedProps>().props;
  const user = auth.user as { name: string; email: string } | null;

  function handleLogout() {
    router.post(route("admin.logout"));
  }

  return (
    <header className="sticky top-0 z-30 flex items-center gap-4 border-b border-espresso-900/10 bg-cream-50/95 px-4 py-3.5 backdrop-blur sm:px-6">
      <button
        type="button"
        onClick={onOpenMobileSidebar}
        aria-label="Buka menu"
        className="rounded-lg p-1.5 text-espresso-700 hover:bg-espresso-900/5 lg:hidden"
      >
        <MenuIcon className="h-5 w-5" />
      </button>

      <nav aria-label="Breadcrumb" className="min-w-0 flex-1 text-sm text-espresso-500">
        <span className="text-espresso-900">Admin</span>
        {title ? (
          <>
            {" "}/ <span className="truncate text-espresso-900">{title}</span>
          </>
        ) : null}
      </nav>

      <a
        href="/"
        target="_blank"
        rel="noopener noreferrer"
        className="hidden items-center gap-1.5 rounded-xl border border-espresso-900/15 px-3.5 py-2 text-sm font-semibold text-espresso-700 hover:bg-espresso-900/5 sm:inline-flex"
      >
        Lihat Situs
        <ExternalLink className="h-4 w-4" aria-hidden="true" />
      </a>

      <Menu as="div" className="relative">
        <MenuButton className="flex items-center gap-2 rounded-xl px-2 py-1.5 text-sm font-semibold text-espresso-900 hover:bg-espresso-900/5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
          <span className="flex h-8 w-8 items-center justify-center rounded-full bg-espresso-900 text-xs font-bold text-cream-50">
            {(user?.name ?? "A").slice(0, 1).toUpperCase()}
          </span>
          <span className="hidden max-w-[10rem] truncate sm:inline">{user?.name}</span>
          <ChevronDown className="h-4 w-4 text-espresso-500" aria-hidden="true" />
        </MenuButton>
        <Transition
          as={Fragment}
          enter="transition ease-out duration-100"
          enterFrom="transform opacity-0 scale-95"
          enterTo="transform opacity-100 scale-100"
          leave="transition ease-in duration-75"
          leaveFrom="transform opacity-100 scale-100"
          leaveTo="transform opacity-0 scale-95"
        >
          <MenuItems className="absolute right-0 mt-2 w-56 origin-top-right rounded-xl border border-espresso-900/10 bg-cream-50 p-1.5 shadow-lift focus:outline-none">
            <div className="px-3 py-2 text-xs text-espresso-500">
              <p className="truncate font-semibold text-espresso-900">{user?.name}</p>
              <p className="truncate">{user?.email}</p>
            </div>
            <div className="my-1 border-t border-espresso-900/10" />
            <MenuItem>
              {({ focus }) => (
                <Link
                  href={route("admin.profile.edit")}
                  className={`flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium ${
                    focus ? "bg-espresso-900/5 text-espresso-950" : "text-espresso-700"
                  }`}
                >
                  <KeyRound className="h-4 w-4" aria-hidden="true" />
                  Ganti Password
                </Link>
              )}
            </MenuItem>
            <MenuItem>
              {({ focus }) => (
                <button
                  type="button"
                  onClick={handleLogout}
                  className={`flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium ${
                    focus ? "bg-red-50 text-red-700" : "text-red-700"
                  }`}
                >
                  <LogOut className="h-4 w-4" aria-hidden="true" />
                  Keluar
                </button>
              )}
            </MenuItem>
          </MenuItems>
        </Transition>
      </Menu>
    </header>
  );
}

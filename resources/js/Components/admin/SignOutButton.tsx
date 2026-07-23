import { router } from "@inertiajs/react";

export function SignOutButton() {
  return (
    <button
      type="button"
      onClick={() => router.post(route("admin.logout"))}
      className="btn-outline !px-4 !py-2 text-sm"
    >
      Keluar
    </button>
  );
}

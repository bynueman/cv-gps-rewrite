import type { ReactNode } from "react";

/** Bare chrome for pre-auth admin pages (login) — no sidebar/topbar. */
export default function GuestLayout({ children }: { children: ReactNode }) {
  return <div className="min-h-screen bg-cream-100 text-espresso-900">{children}</div>;
}

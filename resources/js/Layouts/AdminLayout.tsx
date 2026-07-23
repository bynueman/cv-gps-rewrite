import type { ReactNode } from "react";

/** Admin chrome — no LanguageProvider (admin stays outside the bilingual UI). */
export default function AdminLayout({ children }: { children: ReactNode }) {
  return <div className="min-h-screen bg-cream-100 text-espresso-900">{children}</div>;
}

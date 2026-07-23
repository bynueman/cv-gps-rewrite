import type { ComponentType, SVGProps } from "react";
import { Link } from "@inertiajs/react";
import { Inbox } from "lucide-react";

export function EmptyState({
  icon: Icon = Inbox,
  title,
  description,
  actionLabel,
  actionHref,
}: {
  icon?: ComponentType<SVGProps<SVGSVGElement>>;
  title: string;
  description?: string;
  actionLabel?: string;
  actionHref?: string;
}) {
  return (
    <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-espresso-900/15 bg-cream-50 px-6 py-16 text-center">
      <span className="flex h-12 w-12 items-center justify-center rounded-full bg-espresso-900/5 text-espresso-500">
        <Icon className="h-6 w-6" aria-hidden="true" />
      </span>
      <p className="font-display text-base font-semibold text-espresso-950">{title}</p>
      {description ? <p className="max-w-sm text-sm text-espresso-600">{description}</p> : null}
      {actionLabel && actionHref ? (
        <Link href={actionHref} className="btn-primary mt-2 !px-4 !py-2 text-sm">
          {actionLabel}
        </Link>
      ) : null}
    </div>
  );
}

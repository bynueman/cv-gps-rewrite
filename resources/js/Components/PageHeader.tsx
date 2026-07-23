import type { ReactNode } from "react";
import { useReveal } from "@/hooks/useReveal";
import { MotionBackdrop } from "@/Components/MotionBackdrop";

/**
 * Inner-page hero: kicker + display title + lede on the warm neutral
 * base, with an optional motion backdrop variant per product world.
 */
export function PageHeader({
  kicker,
  title,
  lede,
  variant = "neutral",
  deps = [],
  children,
}: {
  kicker: string;
  title: string;
  lede?: string;
  variant?: "kuicip" | "teko" | "neutral";
  /** re-run reveal when these change (e.g. language) */
  deps?: unknown[];
  children?: ReactNode;
}) {
  const scope = useReveal<HTMLElement>(deps);

  return (
    <header ref={scope} className="relative overflow-hidden pb-14 pt-28 sm:pt-36 lg:pb-20">
      <MotionBackdrop variant={variant} />
      <div className="container-page relative">
        <p data-reveal className="kicker">
          {kicker}
        </p>
        <h1
          data-reveal
          className="mt-4 max-w-3xl font-display text-4xl font-semibold leading-[1.08] tracking-tight sm:text-5xl"
        >
          {title}
        </h1>
        {lede ? (
          <p data-reveal className="mt-5 max-w-2xl text-base leading-relaxed text-espresso-600 sm:text-lg">
            {lede}
          </p>
        ) : null}
        {children}
      </div>
    </header>
  );
}

import { useLayoutEffect, useRef } from "react";
import { gsap } from "@/lib/gsap";

type Variant = "kuicip" | "teko" | "neutral";

/**
 * Decorative animated background layer tied to its parent section.
 * Shapes drift on a slow loop and parallax against scroll (scrubbed),
 * so the page background feels alive without fighting the content.
 *
 * - `data-drift` = parallax factor (higher moves further on scroll)
 * - aria-hidden + pointer-events-none: purely decorative
 * - Disabled under prefers-reduced-motion; loops skipped on mobile
 *
 * Parent section must be `relative`; content above it needs `relative`
 * (any positioned ancestor) so the backdrop stays behind.
 */
export function MotionBackdrop({
  variant = "neutral",
  className,
}: {
  variant?: Variant;
  className?: string;
}) {
  const scope = useRef<HTMLDivElement>(null);

  useLayoutEffect(() => {
    const el = scope.current;
    if (!el?.parentElement) return;
    const section = el.parentElement;
    const mm = gsap.matchMedia(scope);

    mm.add("(prefers-reduced-motion: no-preference)", () => {
      // scroll parallax — all viewports, transform-only
      gsap.utils.toArray<HTMLElement>("[data-drift]").forEach((shape) => {
        const factor = Number(shape.dataset.drift ?? 1);
        gsap.to(shape, {
          yPercent: -22 * factor,
          ease: "none",
          scrollTrigger: {
            trigger: section,
            start: "top bottom",
            end: "bottom top",
            scrub: true,
          },
        });
      });
    });

    mm.add("(prefers-reduced-motion: no-preference) and (min-width: 768px)", () => {
      // gentle idle float — desktop only
      gsap.utils.toArray<HTMLElement>("[data-float]").forEach((shape, i) => {
        gsap.to(shape, {
          y: i % 2 ? 10 : -12,
          rotation: i % 2 ? 4 : -3,
          duration: 3.4 + i * 0.7,
          ease: "sine.inOut",
          yoyo: true,
          repeat: -1,
        });
      });
    });

    return () => mm.revert();
  }, [variant]);

  return (
    <div
      ref={scope}
      aria-hidden="true"
      className={`pointer-events-none absolute inset-0 overflow-hidden ${className ?? ""}`}
    >
      {variant === "kuicip" ? (
        <>
          <svg data-drift="1.4" data-float className="absolute left-[6%] top-[12%] w-14 opacity-25" viewBox="0 0 60 52">
            <path d="M6 30 C2 16 14 4 30 4 c16 0 28 10 24 26 c-3 13 -14 20 -26 18 C15 46 9 40 6 30 Z" fill="#F2B234" />
          </svg>
          <svg data-drift="0.8" data-float className="absolute right-[8%] top-[22%] w-10 opacity-20" viewBox="0 0 60 52">
            <path d="M6 30 C2 16 14 4 30 4 c16 0 28 10 24 26 c-3 13 -14 20 -26 18 C15 46 9 40 6 30 Z" fill="#C05A2E" />
          </svg>
          <svg data-drift="1.1" className="absolute bottom-[18%] left-[14%] w-8 opacity-15" viewBox="0 0 60 52">
            <path d="M6 30 C2 16 14 4 30 4 c16 0 28 10 24 26 c-3 13 -14 20 -26 18 C15 46 9 40 6 30 Z" fill="#3E8E52" />
          </svg>
          <svg data-drift="0.5" className="absolute -right-10 bottom-[8%] w-72 opacity-20" viewBox="0 0 280 120" fill="none">
            <path d="M8 112 C60 30 200 20 272 64" stroke="#C27F0A" strokeWidth="3" strokeLinecap="round" strokeDasharray="1 14" />
          </svg>
        </>
      ) : null}

      {variant === "teko" ? (
        <>
          <svg data-drift="1.2" data-float className="absolute left-[8%] top-[10%] w-16 opacity-25" viewBox="0 0 60 100" fill="none">
            <path d="M30 96 C18 74 42 62 30 40 C22 24 36 14 32 2" stroke="#C06B2D" strokeWidth="3" strokeLinecap="round" />
          </svg>
          <svg data-drift="0.6" className="absolute -left-8 bottom-[14%] w-64 opacity-20" viewBox="0 0 280 120" fill="none">
            <path d="M8 64 C90 8 210 26 272 100" stroke="#8A4A26" strokeWidth="3" strokeLinecap="round" strokeDasharray="1 14" />
          </svg>
          <span data-drift="1.5" data-float className="absolute right-[12%] top-[18%] h-3 w-3 rounded-full bg-turmeric opacity-30" />
          <span data-drift="0.9" data-float className="absolute right-[22%] top-[34%] h-2 w-2 rounded-full bg-ginger opacity-30" />
          <svg data-drift="1.0" data-float className="absolute bottom-[26%] right-[9%] w-9 opacity-25" viewBox="0 0 40 24">
            <path d="M2 20 Q14 -6 38 4 Q26 24 2 20 Z" fill="#5F7A3D" />
          </svg>
        </>
      ) : null}

      {variant === "neutral" ? (
        <>
          <svg data-drift="0.5" className="absolute -left-16 top-[16%] w-[28rem] opacity-[0.16]" viewBox="0 0 440 160" fill="none">
            <path d="M4 130 C120 20 320 30 436 96" stroke="#C27F0A" strokeWidth="10" strokeLinecap="round" />
          </svg>
          <svg data-drift="1.0" className="absolute -right-12 bottom-[12%] w-80 opacity-[0.14]" viewBox="0 0 320 120" fill="none">
            <path d="M6 24 C110 110 230 96 314 30" stroke="#8A4A26" strokeWidth="8" strokeLinecap="round" />
          </svg>
          <span data-drift="1.4" data-float className="absolute left-[18%] top-[42%] h-2.5 w-2.5 rounded-full bg-gold-400 opacity-20" />
          <span data-drift="0.8" data-float className="absolute right-[24%] top-[24%] h-2 w-2 rounded-full bg-ginger opacity-[0.18]" />
        </>
      ) : null}
    </div>
  );
}

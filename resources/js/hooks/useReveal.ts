import { useLayoutEffect, useRef } from "react";
import { gsap } from "@/lib/gsap";

/**
 * Reveals children marked with [data-reveal] as the section scrolls
 * into view. Staggered, subtle, and disabled entirely under
 * prefers-reduced-motion. Re-runs when `deps` change (e.g. language
 * switch re-renders the text nodes).
 */
export function useReveal<T extends HTMLElement>(deps: unknown[] = []) {
  const scope = useRef<T>(null);

  useLayoutEffect(() => {
    const el = scope.current;
    if (!el) return;

    const mm = gsap.matchMedia();

    mm.add("(prefers-reduced-motion: no-preference)", () => {
      const targets = el.querySelectorAll<HTMLElement>("[data-reveal]");
      if (!targets.length) return;
      gsap.fromTo(
        targets,
        { autoAlpha: 0, y: 28 },
        {
          autoAlpha: 1,
          y: 0,
          duration: 0.9,
          ease: "power3.out",
          stagger: 0.12,
          scrollTrigger: {
            trigger: el,
            start: "top 78%",
            once: true,
          },
        }
      );
    });

    return () => mm.revert();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps);

  return scope;
}

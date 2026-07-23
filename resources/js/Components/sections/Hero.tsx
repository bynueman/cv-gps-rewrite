import { useLayoutEffect, useRef } from "react";
import { Link, usePage } from "@inertiajs/react";
import { gsap } from "@/lib/gsap";
import { useLang } from "@/lib/i18n";
import type { SharedProps } from "@/types";

/**
 * Homepage hero. Centered eyebrow/title/subtitle, two soft
 * radial-gradient blobs, a handful of small floating accents, and the
 * two real product-cutout images each carrying a rotated count badge.
 * Parallax + idle float follow the same gsap.matchMedia /
 * prefers-reduced-motion convention as the rest of the site.
 */
export function Hero({ kuicipCount, tekoCount }: { kuicipCount: number; tekoCount: number }) {
  const { lang, t } = useLang();
  const scope = useRef<HTMLElement>(null);
  const { brandAssets } = usePage<SharedProps>().props.site;

  useLayoutEffect(() => {
    const mm = gsap.matchMedia(scope);

    mm.add("(prefers-reduced-motion: no-preference)", () => {
      gsap.fromTo(
        "[data-hero-copy]",
        { autoAlpha: 0, y: 26 },
        { autoAlpha: 1, y: 0, duration: 0.9, stagger: 0.1, ease: "power3.out" }
      );
      gsap.fromTo(
        "[data-hero-cutout]",
        { autoAlpha: 0, y: 40 },
        { autoAlpha: 1, y: 0, duration: 0.9, stagger: 0.12, ease: "power3.out", delay: 0.3 }
      );

      gsap.to("[data-blob-1]", {
        y: 90,
        ease: "none",
        scrollTrigger: { trigger: scope.current, start: "top top", end: "bottom top", scrub: true },
      });
      gsap.to("[data-blob-2]", {
        y: -54,
        ease: "none",
        scrollTrigger: { trigger: scope.current, start: "top top", end: "bottom top", scrub: true },
      });
      gsap.to("[data-hero-cutout]", {
        y: -30,
        ease: "none",
        stagger: 0.1,
        scrollTrigger: { trigger: scope.current, start: "top top", end: "bottom top", scrub: true },
      });

      gsap.utils.toArray<HTMLElement>("[data-float]").forEach((el, i) => {
        gsap.to(el, {
          y: i % 2 ? 12 : -14,
          duration: 5.5 + i * 0.6,
          ease: "sine.inOut",
          yoyo: true,
          repeat: -1,
        });
      });
    });

    return () => mm.revert();
  }, [lang]);

  return (
    <section
      ref={scope}
      className="relative flex min-h-screen items-center justify-center overflow-hidden px-6 pb-24 pt-40 sm:px-14 sm:pt-44"
    >
      <div
        data-blob-1
        aria-hidden="true"
        className="pointer-events-none absolute -right-40 -top-40 h-[36rem] w-[36rem] rounded-full"
        style={{
          background:
            "radial-gradient(circle at 35% 35%, oklch(82% 0.16 92), oklch(78% 0.17 92 / 0))",
        }}
      />
      <div
        data-blob-2
        aria-hidden="true"
        className="pointer-events-none absolute -bottom-56 -left-44 h-[32rem] w-[32rem] rounded-full"
        style={{ background: "radial-gradient(circle at 60% 40%, oklch(56% 0.07 135 / 0.5), oklch(56% 0.07 135 / 0))" }}
      />
      <div
        data-float
        aria-hidden="true"
        className="pointer-events-none absolute left-[8%] top-32 h-8 w-16 rounded-full bg-homeTerracotta/85"
      />
      <div
        data-float
        aria-hidden="true"
        className="pointer-events-none absolute left-[4%] top-64 h-5 w-9 rounded-full bg-homeGold"
      />
      <div
        data-float
        aria-hidden="true"
        className="pointer-events-none absolute right-[10%] top-40 h-3 w-16 rounded-full border-2 border-homeSage/60 bg-homeCard/90"
      />
      <div
        data-float
        aria-hidden="true"
        className="pointer-events-none absolute right-[14%] top-52 h-2.5 w-11 rounded-full border-2 border-homeSage/40"
      />

      <div className="relative z-10 flex max-w-3xl flex-col items-center gap-6 text-center">
        <h1
          data-hero-copy
          className="font-display text-5xl leading-[1.04] text-homeInk sm:text-6xl lg:text-[74px]"
        >
          {t.hero.title}
        </h1>
        <p data-hero-copy className="max-w-xl text-lg leading-relaxed text-homeInk2">
          {t.hero.subtitle}
        </p>
        <div data-hero-copy className="mt-1.5 flex flex-wrap justify-center gap-4">
          <Link
            href="/products"
            className="rounded-full bg-homeTerracotta px-8 py-4 text-[15px] font-bold text-homeBg shadow-[0_16px_30px_-12px_oklch(64%_0.19_38_/_0.55)] transition-colors hover:bg-homeTerracottaDark"
          >
            {t.hero.ctaProducts}
          </Link>
          <a
            href="#about"
            className="rounded-full border-[1.5px] border-homeInk/35 px-8 py-4 text-[15px] font-bold text-homeInk transition-colors hover:bg-homeInk hover:text-homeBg"
          >
            {t.hero.ctaAbout}
          </a>
        </div>
      </div>

      <div
        data-hero-cutout
        className="pointer-events-none absolute bottom-[2%] left-[4%] hidden w-56 drop-shadow-[0_30px_40px_oklch(27%_0.045_50_/_0.3)] sm:block lg:w-64"
      >
        <img src={brandAssets.group_shots.kuicip} alt="Kuicip" className="h-auto w-full" />
        <span className="absolute -right-4 -top-4 -rotate-6 rounded-full bg-homeGold px-3.5 py-2 text-xs font-extrabold text-homeInk shadow-[0_8px_16px_-6px_oklch(27%_0.045_50_/_0.4)]">
          {kuicipCount} {t.hero.heroBadgeKuicipLabel}
        </span>
      </div>
      <div
        data-hero-cutout
        className="pointer-events-none absolute bottom-[4%] right-[4%] hidden w-52 drop-shadow-[0_30px_40px_oklch(27%_0.045_50_/_0.3)] sm:block lg:w-60"
      >
        <img src={brandAssets.group_shots["putri-teko"]} alt="Putri Teko" className="h-auto w-full" />
        <span className="absolute -left-4 -top-4 rotate-6 rounded-full bg-homeSage px-3.5 py-2 text-xs font-extrabold text-homeBg shadow-[0_8px_16px_-6px_oklch(27%_0.045_50_/_0.4)]">
          {tekoCount} {t.hero.heroBadgeTekoLabel}
        </span>
      </div>
    </section>
  );
}

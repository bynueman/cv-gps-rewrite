import { useLayoutEffect, useRef } from "react";
import { Link } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import { gsap, Draggable } from "@/lib/gsap";
import type { Partner } from "@/types";

const PX_PER_SECOND = 45;

function LogoItem({ partner }: { partner: Partner }) {
  return (
    <span className="flex h-14 w-32 shrink-0 items-center justify-center opacity-70 grayscale transition-all duration-300 hover:opacity-100 hover:grayscale-0">
      {partner.logo ? (
        <img
          src={partner.logo}
          alt={partner.name}
          draggable={false}
          className="max-h-full max-w-full select-none object-contain"
        />
      ) : (
        <span className="font-display text-sm font-semibold text-homeInk2">{partner.name}</span>
      )}
    </span>
  );
}

/**
 * Admin-managed trust wall (Pengaturan → Mitra). Renders nothing when
 * there are no active partners yet, rather than showing an empty
 * section to visitors.
 *
 * The row auto-scrolls left in an infinite loop, pauses on hover, and
 * is draggable left/right (with inertia on release) — the track is
 * rendered twice back-to-back so wrapping the drag/scroll position at
 * the halfway point looks seamless either direction.
 */
export function PartnersLogoWall({ partners }: { partners: Partner[] }) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);
  const trackRef = useRef<HTMLDivElement>(null);
  const wrapperRef = useRef<HTMLDivElement>(null);

  useLayoutEffect(() => {
    const track = trackRef.current;
    const wrapper = wrapperRef.current;
    if (!track || !wrapper || partners.length === 0) return;

    // Half the track's width — the content is duplicated, so this is
    // exactly one full cycle of the original (undoubled) list.
    const cycleWidth = track.scrollWidth / 2;
    const wrapX = gsap.utils.wrap(-cycleWidth, 0);

    // A ticker-driven position (rather than a fixed-duration repeating
    // tween) so resuming after a drag/hover pause continues from
    // exactly where the drag left off, with no snap-back.
    let x = 0;
    let autoScroll = true;
    let dragging = false;

    gsap.set(track, { x: 0 });

    const mm = gsap.matchMedia();
    let allowAutoScroll = true;
    mm.add("(prefers-reduced-motion: no-preference)", () => {
      allowAutoScroll = true;
      return () => {
        allowAutoScroll = false;
      };
    });
    mm.add("(prefers-reduced-motion: reduce)", () => {
      allowAutoScroll = false;
    });

    function tick(_time: number, deltaMs: number) {
      if (autoScroll && !dragging && allowAutoScroll) {
        x -= (PX_PER_SECOND * deltaMs) / 1000;
        gsap.set(track, { x: wrapX(x) });
      }
    }
    gsap.ticker.add(tick);

    const pause = () => {
      autoScroll = false;
    };
    const resume = () => {
      autoScroll = true;
    };

    wrapper.addEventListener("mouseenter", pause);
    wrapper.addEventListener("mouseleave", resume);

    const [draggable] = Draggable.create(track, {
      type: "x",
      inertia: true,
      allowNativeTouchScrolling: false,
      onPress: () => {
        dragging = true;
      },
      onDrag: function () {
        x = this.x;
        gsap.set(track, { x: wrapX(x) });
      },
      onThrowUpdate: function () {
        x = this.x;
        gsap.set(track, { x: wrapX(x) });
      },
      onDragEnd: () => {
        dragging = false;
      },
      onThrowComplete: () => {
        dragging = false;
      },
    });

    return () => {
      wrapper.removeEventListener("mouseenter", pause);
      wrapper.removeEventListener("mouseleave", resume);
      gsap.ticker.remove(tick);
      draggable.kill();
      mm.revert();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [partners, lang]);

  if (partners.length === 0) {
    return null;
  }

  return (
    <section ref={scope} className="py-20">
      <div className="mx-auto max-w-6xl px-6 text-center sm:px-14">
        <p data-reveal className="text-[13px] font-extrabold uppercase tracking-[0.08em] text-homeTerracotta">
          {t.partnersSection.kicker}
        </p>
        <h2 data-reveal className="mt-3 font-display text-2xl leading-tight text-homeInk sm:text-3xl">
          {t.partnersSection.title}
        </h2>
      </div>

      <div
        ref={wrapperRef}
        data-reveal
        className="mt-10 overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_5%,black_95%,transparent)]"
      >
        <div ref={trackRef} className="flex w-max cursor-grab items-center gap-16 py-2 active:cursor-grabbing">
          {partners.map((partner) => (
            <LogoItem key={`a-${partner.name}`} partner={partner} />
          ))}
          {partners.map((partner) => (
            <LogoItem key={`b-${partner.name}`} partner={partner} />
          ))}
        </div>
      </div>

      <div data-reveal className="mt-10 text-center">
        <Link href="/mitra" className="text-sm font-semibold text-homeTerracotta hover:underline">
          {t.common.seeAll} →
        </Link>
      </div>
    </section>
  );
}

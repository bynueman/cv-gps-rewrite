import type { ReactNode } from "react";

/**
 * Image slot for product photography. While `src` is null (no real
 * packshot yet) it renders the SVG placeholder passed as children;
 * once a path is set it switches to a real <img>.
 *
 * `fit="contain"` suits transparent cutouts; `fit="cover"` suits the
 * opaque studio shots (1086×1448, warm cream backdrop) that fill a
 * rounded frame edge-to-edge.
 */
export function Packshot({
  src,
  alt,
  className,
  imgClassName,
  fit = "contain",
  children,
}: {
  src: string | null;
  alt: string;
  className?: string;
  imgClassName?: string;
  fit?: "contain" | "cover";
  sizes?: string;
  children: ReactNode;
}) {
  if (src) {
    return (
      <div className={`relative ${className ?? ""}`}>
        <img
          src={src}
          alt={alt}
          className={`absolute inset-0 h-full w-full ${fit === "cover" ? "object-cover" : "object-contain"} ${imgClassName ?? ""}`}
        />
      </div>
    );
  }
  return <div className={className}>{children}</div>;
}

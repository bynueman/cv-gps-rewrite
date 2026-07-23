import type { Product } from "@/types";
import type { Lang } from "@/lib/i18n";
import { Packshot } from "@/Components/Packshot";
import { PouchSVG } from "@/Components/graphics/PouchSVG";
import { BottleSVG } from "@/Components/graphics/BottleSVG";
import { SachetSVG } from "@/Components/graphics/SachetSVG";

/**
 * Product image with packaging-aware framing. All real photos are
 * opaque 3:4 studio shots on a warm cream backdrop, so they render as
 * `object-cover` inside a rounded frame; the aspect ratio varies by
 * packaging so jars get more base room while pouches/bottles/boxes
 * keep an upright portrait frame. Products without a photo fall back
 * to the SVG placeholder packshots.
 */
export function ProductPackshot({
  product,
  lang,
  className,
  zoom = false,
  rounded = "rounded-2xl",
}: {
  product: Product;
  lang: Lang;
  className?: string;
  /** adds a slow ken-burns zoom on parent `group` hover */
  zoom?: boolean;
  rounded?: string;
}) {
  const alt =
    product.brand === "kuicip" ? `Kuicip ${product.name[lang]}` : `Putri Teko ${product.name[lang]}`;

  if (product.image) {
    const aspect =
      product.packaging === "toples"
        ? "aspect-[4/4.2]"
        : product.packaging === "kemasan"
          ? "aspect-[4/4.6]"
          : "aspect-[3/4]";
    return (
      <Packshot
        src={product.image}
        alt={alt}
        fit="cover"
        className={`${aspect} ${rounded} overflow-hidden ${className ?? ""}`}
        imgClassName={zoom ? "transition-transform duration-500 group-hover:scale-[1.06]" : undefined}
      >
        {null}
      </Packshot>
    );
  }

  const aspect =
    product.brand === "kuicip"
      ? "aspect-[200/260]"
      : product.group === "brew"
        ? "aspect-[200/240]"
        : "aspect-[120/300]";

  return (
    <Packshot src={null} alt={alt} className={`${aspect} ${className ?? ""}`}>
      {product.brand === "kuicip" ? (
        <PouchSVG
          color={product.color}
          colorDark={product.colorDark}
          flavor={product.name[lang]}
          className="h-full w-full"
        />
      ) : product.group === "brew" ? (
        <SachetSVG label="Putri Teko" product={product.name[lang]} color={product.color} className="h-full w-full" />
      ) : (
        <BottleSVG label="Putri Teko" product={product.name[lang]} liquid={product.color} className="h-full w-full" />
      )}
    </Packshot>
  );
}

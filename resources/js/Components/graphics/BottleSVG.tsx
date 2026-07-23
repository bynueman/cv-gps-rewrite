/**
 * Stylized ready-to-drink bottle used as a placeholder packshot until
 * real photography is dropped into public/images/putri-teko.
 */
export function BottleSVG({
  label,
  product,
  liquid = "#B4652A",
  className,
}: {
  label: string;
  product?: string;
  liquid?: string;
  className?: string;
}) {
  return (
    <svg viewBox="0 0 120 300" role="img" aria-label={`${label}${product ? ` — ${product}` : ""}`} className={className}>
      <rect x="44" y="8" width="32" height="24" rx="4" fill="#3C2B1B" />
      <rect x="44" y="14" width="32" height="3" fill="#FCF9F2" opacity="0.25" />
      <path
        d="M48 32 h24 v22 c0 6 14 12 16 24 l4 34 v160 c0 12 -9 20 -20 20 H48 c-11 0 -20 -8 -20 -20 V112 l4 -34 c2 -12 16 -18 16 -24 Z"
        fill="#E9DDC6"
      />
      <path
        d="M30 118 l2 -18 c10 -6 46 -6 56 0 l2 18 v154 c0 9 -7 16 -16 16 H46 c-9 0 -16 -7 -16 -16 Z"
        fill={liquid}
      />
      <path d="M38 96 c0 60 0 130 0 176 c0 6 2 10 6 12 h-4 c-6 -2 -8 -6 -8 -12 V100 Z" fill="#fff" opacity="0.28" />
      <rect x="26" y="148" width="68" height="80" rx="6" fill="#FCF9F2" />
      <rect x="26" y="148" width="68" height="8" fill="#8A4A26" />
      <rect x="26" y="220" width="68" height="8" fill="#8A4A26" />
      <text
        x="60"
        y="182"
        textAnchor="middle"
        fontFamily="var(--font-display), Arial Black, sans-serif"
        fontWeight="600"
        fontSize="13"
        fill="#2C1F14"
      >
        {label}
      </text>
      {product ? (
        <text
          x="60"
          y="199"
          textAnchor="middle"
          fontFamily="var(--font-sans), cursive"
          fontWeight="600"
          fontSize="8.5"
          fill={liquid}
        >
          {product}
        </text>
      ) : null}
      <text
        x="60"
        y="213"
        textAnchor="middle"
        fontFamily="var(--font-sans), cursive"
        fontSize="6.5"
        letterSpacing="1.4"
        fill="#8A4A26"
      >
        MINUMAN TRADISIONAL
      </text>
    </svg>
  );
}

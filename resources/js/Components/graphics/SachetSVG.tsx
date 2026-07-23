/**
 * Stylized brew-pack / sachet used as a placeholder packshot for
 * Putri Teko ready-to-brew products until real photography is added.
 */
export function SachetSVG({
  label,
  product,
  color = "#8A4A26",
  className,
}: {
  label: string;
  product?: string;
  color?: string;
  className?: string;
}) {
  return (
    <svg viewBox="0 0 200 240" role="img" aria-label={`${label}${product ? ` — ${product}` : ""}`} className={className}>
      <path
        d="M36 40 h128 c4 0 7 3 7 7 l6 168 c1 9 -6 17 -15 17 H38 c-9 0 -16 -8 -15 -17 l6 -168 c0 -4 3 -7 7 -7 Z"
        fill={color}
      />
      <path
        d="M36 40 h18 l-5 192 H38 c-9 0 -16 -8 -15 -17 l6 -168 c0 -4 3 -7 7 -7 Z"
        fill="#000"
        opacity="0.10"
      />
      <path d="M146 40 h18 c4 0 7 3 7 7 l6 168 c1 9 -6 17 -15 17 h-16 Z" fill="#fff" opacity="0.10" />
      <rect x="32" y="26" width="136" height="16" rx="3" fill="#3C2B1B" />
      <g stroke="#FCF9F2" strokeWidth="1.5" opacity="0.4">
        {Array.from({ length: 9 }).map((_, i) => (
          <line key={i} x1={44 + i * 14} y1="28" x2={44 + i * 14} y2="40" />
        ))}
      </g>
      <circle cx="100" cy="176" r="26" fill="#E9DDC6" />
      <circle cx="92" cy="170" r="5" fill="#8A4A26" />
      <circle cx="106" cy="182" r="4" fill="#5E3B1E" />
      <path d="M104 164 q8 -4 14 2 q-8 4 -14 -2 Z" fill="#5F7A3D" />
      <path d="M86 184 q-6 4 -4 10 q6 -2 4 -10 Z" fill="#C06B2D" />
      <path d="M44 78 c34 -12 78 -12 112 0 l2 44 c-38 10 -78 10 -116 0 Z" fill="#FCF9F2" />
      <text
        x="100"
        y="104"
        textAnchor="middle"
        fontFamily="var(--font-display), Arial Black, sans-serif"
        fontWeight="600"
        fontSize="19"
        fill="#2C1F14"
      >
        {label}
      </text>
      {product ? (
        <text
          x="100"
          y="120"
          textAnchor="middle"
          fontFamily="var(--font-sans), cursive"
          fontWeight="600"
          fontSize="10"
          fill={color}
        >
          {product}
        </text>
      ) : null}
    </svg>
  );
}

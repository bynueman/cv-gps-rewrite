/**
 * Stylized Kuicip ziplock pouch used as a placeholder packshot until
 * real photography is dropped into public/images/kuicip. The zip line
 * carries the class "pouch-zip" so hero animations can target it.
 */
export function PouchSVG({
  color = "#F2B234",
  colorDark = "#9C6508",
  label = "KUICIP",
  flavor,
  weight = "70 gr",
  className,
}: {
  color?: string;
  colorDark?: string;
  label?: string;
  flavor?: string;
  weight?: string | null;
  className?: string;
}) {
  return (
    <svg
      viewBox="0 0 200 260"
      role="img"
      aria-label={`${label}${flavor ? ` — ${flavor}` : ""}`}
      className={className}
    >
      <path
        d="M28 34 h144 c3 0 5 2 5 5 l8 196 c0.4 8 -5 15 -13 15 H28 c-8 0 -13.4 -7 -13 -15 l8 -196 c0 -3 2 -5 5 -5 Z"
        fill={color}
      />
      <path
        d="M28 34 h20 l-6 216 H28 c-8 0 -13.4 -7 -13 -15 l8 -196 c0 -3 2 -5 5 -5 Z"
        fill="#000"
        opacity="0.08"
      />
      <path
        d="M152 34 h20 c3 0 5 2 5 5 l8 196 c0.4 8 -5 15 -13 15 h-14 Z"
        fill="#fff"
        opacity="0.10"
      />
      <rect x="24" y="18" width="152" height="16" rx="4" fill={colorDark} />
      <line
        className="pouch-zip"
        x1="30"
        y1="26"
        x2="170"
        y2="26"
        stroke="#FCF9F2"
        strokeWidth="3"
        strokeLinecap="round"
        strokeDasharray="6 5"
      />
      <path
        d="M40 96 c30 -14 90 -14 120 0 l3 74 c-32 12 -94 12 -126 0 Z"
        fill="#FCF9F2"
      />
      <text
        x="100"
        y="132"
        textAnchor="middle"
        fontFamily="var(--font-display), Arial Black, sans-serif"
        fontWeight="700"
        fontSize="30"
        fill="#2C1F14"
      >
        {label}
      </text>
      {flavor ? (
        <text
          x="100"
          y="156"
          textAnchor="middle"
          fontFamily="var(--font-sans), cursive"
          fontWeight="600"
          fontSize="12"
          fill={colorDark}
        >
          {flavor}
        </text>
      ) : null}
      {weight ? (
        <g>
          <rect x="134" y="216" width="46" height="20" rx="10" fill="#FCF9F2" opacity="0.92" />
          <text
            x="157"
            y="230"
            textAnchor="middle"
            fontFamily="var(--font-sans), cursive"
            fontWeight="700"
            fontSize="11"
            fill="#2C1F14"
          >
            {weight}
          </text>
        </g>
      ) : null}
      <g transform="translate(96 198)">
        <path
          d="M-26 6 c-4 -14 8 -24 24 -22 c16 2 26 12 20 24 c-5 11 -18 14 -30 10 c-8 -3 -12 -6 -14 -12 Z"
          fill={colorDark}
          opacity="0.9"
        />
        <path
          d="M-14 2 c-2 -8 6 -13 14 -12 c9 1 15 7 12 14 c-3 6 -11 8 -18 5 c-5 -2 -7 -4 -8 -7 Z"
          fill="#FCF9F2"
          opacity="0.35"
        />
      </g>
    </svg>
  );
}

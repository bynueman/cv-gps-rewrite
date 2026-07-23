/**
 * Tailwind's `text-x/50` opacity-modifier only works on colors it can
 * decompose. A raw `"oklch(64% 0.19 38)"` string is opaque to that
 * mechanism — `text-homeBg/60` silently fails to generate any rule at
 * all, so the class does nothing and the color falls back to
 * whatever's inherited. oklch() itself supports a slash-alpha channel
 * (`oklch(L C H / A)`), so wrapping each channel string in the
 * standard Tailwind opacity-value function fixes every `/NN` usage.
 */
function oklchColor(channels) {
  return ({ opacityValue }) =>
    opacityValue === undefined
      ? `oklch(${channels})`
      : `oklch(${channels} / ${opacityValue})`;
}

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.{ts,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Neutral base — warm cream / espresso (the 80%)
        cream: {
          50: "#FCF9F2",
          100: "#F7F1E5",
          200: "#EFE5D1",
          300: "#E3D4B8",
        },
        espresso: {
          950: "#231810",
          900: "#2C1F14",
          800: "#3C2B1B",
          700: "#4F3923",
          600: "#6A4D30",
          500: "#8A6845",
        },
        // Brand foundation — Kuicip golden yellow + deep brown (the 15%)
        gold: {
          300: "#F7C75E",
          400: "#F2B234",
          500: "#E29A12",
          600: "#C27F0A",
          700: "#9C6508",
        },
        // Putri Teko — earthy amber / ginger / tamarind / herbal (accents)
        ginger: "#C06B2D",
        tamarind: "#8A4A26",
        palmsugar: "#5E3B1E",
        turmeric: "#D98E23",
        herbal: "#5F7A3D",

        // Homepage redesign only — additive palette, scoped to
        // resources/js/Pages/Home.tsx + its section components. Do not
        // use elsewhere; every other page keeps the cream/espresso/gold
        // palette above.
        homeBg: oklchColor("97% 0.025 85"),
        homeCard: oklchColor("96% 0.02 85"),
        homePanel: oklchColor("94% 0.035 80"),
        homeInk: oklchColor("27% 0.045 50"),
        homeInk2: oklchColor("45% 0.05 50"),
        homeTerracotta: oklchColor("64% 0.19 38"),
        homeTerracottaDark: oklchColor("56% 0.19 38"),
        homeGold: oklchColor("78% 0.17 92"),
        homeGoldLight: oklchColor("84% 0.17 92"),
        homeSage: oklchColor("56% 0.07 135"),
        homeSageDark: oklchColor("48% 0.07 135"),
        homeKuicipPanel: oklchColor("93% 0.05 55"),
        homeTekoPanel: oklchColor("92% 0.03 100"),
      },
      fontFamily: {
        display: ["var(--font-display)", "Arial Black", "sans-serif"],
        // Literal "Comic Sans MS" first (real thing, when the OS has it),
        // Comic Neue (loaded webfont) as the guaranteed fallback.
        sans: ["Comic Sans MS", "var(--font-sans)", "cursive"],
      },
      maxWidth: {
        page: "76rem",
      },
      boxShadow: {
        lift: "0 14px 34px -14px rgba(44, 31, 20, 0.28)",
        card: "0 6px 20px -10px rgba(44, 31, 20, 0.22)",
      },
    },
  },
  plugins: [],
};

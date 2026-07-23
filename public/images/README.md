# Image assets

All product photos, logos, and group cutouts referenced by
`src/lib/content.ts` live here. Every current SKU points at a real
file; if a future SKU has no photo yet, leave its `image: null` and
the site falls back to SVG placeholder packshots.

All files are **compressed WebP** (max 1200 px long edge; quality 82
opaque / 90 with alpha). The original ~2 MB PNG masters are not in the
repo — keep them archived separately and re-run a WebP export when
replacing an asset.

## Current inventory

| Path | What it is | Used by |
| --- | --- | --- |
| `logo/gps.webp` | corporate mark (red flower + blue "GPS"), transparent | Header, Footer (`logos.gps`) |
| `logo/kuicip.webp` | Kuicip cartoon wordmark, transparent | Hero trust line, ProductsOverview, ProductFamilies |
| `logo/putriteko.webp` | Putri Teko art-nouveau emblem, transparent (portrait) | Hero trust line, ProductsOverview, ProductFamilies |
| `kuicip-nobg.webp` | transparent 3-pouch fan (Seaweed · Original · Truffle) | Hero + ProductFamilies layered scenes (`groupShots.kuicip`) |
| `putriteko-nobg.webp` | transparent group of 5 kotak sachet boxes (Susu Jahe, Jahe Cokelat, Jahe Serai, Jahe Merah, Wedang Uwuh) | Hero + ProductFamilies layered scenes (`groupShots["putri-teko"]`) |
| `kuicip/kuicip-*.webp` | 8 studio packshots (ori, balado, barbeque, chililime, rendang, seaweed, spicymala, truffle), opaque 3:4, warm cream backdrop | `kuicipProducts[*].image` |
| `putri-teko/boto*-*.webp` | 3 RTD bottles: `boto-beraskencur` (note the filename typo — keep in sync with content.ts), `botol-gulaasam`, `botol-kuniasam` | botol SKUs |
| `putri-teko/kotak-*.webp` | 5 batik sachet boxes: jahecoklat, jahemerah, jaheserai, susujahe, wedanguwuh | kotak SKUs |
| `putri-teko/toples-*.webp` | 4 jars: gulabatukristal, jahemerah, jaheserai, wedanguwuh | toples SKUs |
| `putri-teko/kemasan-ori.webp` | 1 practical clear spice pack | kemasan SKU |
| `putri-teko/besek-wedanguwuh.webp` | 1 hand-woven bamboo gift basket | besek SKU |
| `news/` | (empty) — News & Activities is empty pending an admin panel; drop real editorial photos here once articles exist | `articles[*].image` |

## Guidelines

- The studio packshots are opaque with a shared warm cream backdrop —
  they render `object-cover` inside rounded card frames
  (`ProductPackshot.tsx`), so keep new photos on the same backdrop
  and roughly 3:4 portrait to match.
- Transparent assets (logos, `*-nobg`) render `object-contain` and can
  float on colored planes.
- New assets: export as WebP, max ~1200 px on the long edge (the site
  never renders larger than ~800 px), then reference from
  `content.ts`.
- Card aspect ratios per packaging live in `ProductPackshot.tsx`:
  pouch/botol/kotak/besek 3:4 · toples ≈ square · kemasan 4:4.6.

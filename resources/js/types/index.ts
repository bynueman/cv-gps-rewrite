export type Bilingual = { id: string; en: string };

export type Company = {
  name: string;
  short_name: string;
  location: Bilingual;
  address: string;
  email: string;
  email_alt: string | null;
  whatsapp: string;
  whatsapp_href: string;
  hours: Bilingual;
  map_embed_url: string;
};

export type BrandAssets = {
  logos: { gps: string; kuicip: string; "putri-teko": string };
  group_shots: { kuicip: string; "putri-teko": string };
  fostered_by: { name: string; logo: string }[];
};

export type BrandKey = "kuicip" | "putri-teko";

export type Partner = {
  name: string;
  category: "ritel" | "hotel" | "restaurant" | "oleh-oleh" | "cakery" | "institusi";
  logo: string | null;
};

/** Matches the shape API Resources (BrandResource) send — nested
 * Bilingual fields, same contract as the original content.ts `brands`. */
export type Brand = {
  key: BrandKey;
  name: string;
  tag: Bilingual;
  weight: string | null;
  story: Bilingual;
  href: string;
};

export type TekoGroup = "rtd" | "brew";
export type TekoPackaging = "botol" | "kotak" | "toples" | "kemasan" | "besek";

/** Matches ProductResource's output — same contract as the original
 * content.ts `Product` type. */
export type Product = {
  slug: string;
  brand: BrandKey;
  group?: TekoGroup | null;
  packaging?: TekoPackaging | null;
  name: Bilingual;
  short: Bilingual;
  personality: Bilingual;
  serving?: Bilingual | null;
  description: Bilingual;
  highlights: Bilingual[];
  notes: Bilingual[];
  color: string;
  colorDark: string;
  image: string | null;
  featured?: boolean;
  placeholder?: boolean;
};

/** Matches ArticleResource's output — same contract as the original
 * content.ts `Article` type (body holds one HTML blob per language). */
export type Article = {
  slug: string;
  date: string;
  category: Bilingual;
  title: Bilingual;
  excerpt: Bilingual;
  body: Bilingual[];
  image: string | null;
  imageThumb?: string | null;
  imageOg?: string | null;
  featured?: boolean;
  tags?: string[];
};

export type SharedProps = {
  auth: {
    user: { id: number; name: string; email: string; role: "superadmin" | "editor" } | null;
  };
  site: {
    company: Company;
    brandAssets: BrandAssets;
    brands: Brand[];
  };
  flash: {
    status: string | null;
    error: string | null;
  };
  unreadMessagesCount?: number;
};

import { createContext, useContext, useState, type ReactNode } from "react";

export type Lang = "id" | "en";

/**
 * Single source of truth for all visible UI copy.
 * `id` is the canonical shape; `en` is type-checked against it, so a
 * missing translation is a compile error. Product/article content that
 * is data rather than UI copy comes from the backend (see resources/js
 * page props) with { id, en } fields.
 */
const id = {
  nav: {
    home: "Beranda",
    products: "Produk Kami",
    export: "Ekspor",
    news: "Berita & Kegiatan",
    contact: "Hubungi Kami",
    inquiry: "Hubungi Kami",
    partners: "Mitra",
  },
  common: {
    seeAll: "Lihat semua",
    seeAllProducts: "Lihat semua produk",
    readMore: "Baca selengkapnya",
    backToNews: "Kembali ke Berita & Kegiatan",
    backToProducts: "Kembali ke katalog",
    exploreBrand: "Jelajahi",
    viewDetail: "Lihat detail",
    home: "Beranda",
    relatedArticles: "Artikel terkait",
    editableNote: "Konten placeholder — sesuaikan dengan data final.",
  },
  /** short badge labels for the real Putri Teko packaging forms */
  packaging: {
    botol: "Botol",
    kotak: "Kotak Sachet",
    toples: "Toples",
    kemasan: "Kemasan",
    besek: "Besek",
  },
  hero: {
    kicker: "Gama Putra Santosa · Sleman, Yogyakarta",
    title: "Berpikir Global, Bertindak Lokal",
    subtitle:
      "Kuicip yang renyah dan Putri Teko yang menghangatkan adalah dua merek unggulan GPS Group, perusahaan makanan & minuman Yogyakarta sejak 2011.",
    ctaProducts: "Jelajahi Produk",
    ctaAbout: "Tentang Kami",
    heroBadgeKuicipLabel: "Varian Kuicip",
    heroBadgeTekoLabel: "Racikan Putri Teko",
  },
  intro: {
    kicker: "Tentang Kami",
    title: "Salah satu perusahaan makanan & minuman terkemuka di Yogyakarta, sejak 2011.",
    body: "CV Gama Putra Santosa (GPS Group) berdiri sejak 2011 di Sleman, DI Yogyakarta. Kami menaungi tiga merek, yaitu Ngayogyakarya, Putri Teko, dan Kuicip, dengan lini minuman tradisional, minuman rempah botanical, dan camilan yang dipasarkan lewat jaringan retail dan modern trade di seluruh Indonesia, dengan visi tumbuh ke pasar mancanegara. Perjalanan ini kami jalani dengan integritas, semangat kewirausahaan, dan inovasi berkelanjutan, selalu berusaha menjadi yang terbaik.",
    facts: [
      { label: "Berdiri", value: "2011" },
      { label: "Basis Produksi", value: "Sleman, DI Yogyakarta" },
      { label: "Menaungi", value: "Ngayogyakarya · Putri Teko · Kuicip" },
      { label: "Jangkauan", value: "Retail & Modern Trade Nasional" },
      { label: "Sertifikasi", value: "BPOM RI · P-IRT · Halal" },
    ],
  },
  featured: {
    kicker: "Produk Unggulan",
    title: "Pilihan Terbaik dari Dua Dunia Rasa",
  },
  partnersSection: {
    kicker: "Mitra Kami",
    title: "Dipercaya oleh mitra ritel, hotel, dan kuliner.",
  },
  partnersPage: {
    kicker: "Mitra",
    title: "Jaringan mitra kami di seluruh Indonesia.",
    subtitle: "Dari ritel nasional hingga institusi pemerintah — berikut mitra yang telah mempercayai produk kami.",
    all: "Semua",
    categories: {
      ritel: "Ritel",
      hotel: "Hotel",
      restaurant: "Restoran",
      "oleh-oleh": "Oleh-Oleh",
      cakery: "Cakery",
      institusi: "Institusi",
    },
    empty: "Belum ada mitra di kategori ini.",
  },
  families: {
    kuicipTagline: "Renyah, berani, dan bikin nagih — keripik singkong dengan 8 varian rasa khas Nusantara.",
    kuicipCta: "Jelajahi Kuicip",
    tekoTagline: "Racikan hangat warisan leluhur — herbal, menenangkan, penuh makna.",
    tekoCta: "Jelajahi Putri Teko",
    center: "1 Perusahaan, 2 Dunia Rasa",
  },
  /** Home-only export teaser — do NOT reuse on /export (see exportSection) */
  homeExport: {
    kicker: "Ekspor",
    title: "Membangun Kemitraan Ekspor Global",
    desc: "Kami membuka peluang kerja sama distribusi dan ekspor bagi mitra yang ingin menghadirkan cita rasa Yogyakarta ke pasar internasional.",
    cards: [
      {
        title: "Kapasitas Produksi",
        desc: "Fasilitas produksi di Sleman dengan kapasitas skala menengah dan standar higienis.",
      },
      {
        title: "Standar Kualitas",
        desc: "Proses produksi mengikuti standar keamanan pangan untuk pasar domestik dan ekspor.",
      },
      {
        title: "Kemasan Siap Ekspor",
        desc: "Kemasan dirancang tahan perjalanan panjang dan sesuai regulasi negara tujuan.",
      },
    ],
    panelTitle: "Tertarik Menjadi Mitra Ekspor?",
    panelDesc: "Sampaikan kebutuhan Anda, tim kami akan menindaklanjuti dalam 2x24 jam kerja.",
    fieldName: "Nama Perusahaan",
    fieldEmail: "Email Bisnis",
    fieldCountry: "Negara Tujuan",
    panelCta: "Kirim Permintaan Ekspor",
  },
  /** Home-only contact pathways — do NOT reuse on /contact (see contactSection) */
  homeContact: {
    kicker: "Kontak",
    title: "Mari Terhubung dengan Kami",
    emailCta: "Kirim email",
    pathways: [
      { title: "Pertanyaan Umum", desc: "Informasi umum tentang perusahaan." },
      { title: "Pertanyaan Produk", desc: "Tanya soal Kuicip & Putri Teko." },
      { title: "Kerjasama Ekspor", desc: "Jadi mitra distribusi ekspor kami." },
      { title: "Kolaborasi", desc: "Peluang kemitraan & kolaborasi lain." },
    ],
  },
  exportSection: {
    kicker: "Ekspor & Kemitraan",
    title: "Portofolio kami terbuka untuk dieksplorasi.",
    subtitle:
      "Kami menyambut percakapan dengan distributor, peritel, dan calon buyer — dari penjajakan produk hingga diskusi kebutuhan pasar tujuan.",
    blocks: [
      {
        title: "Eksplorasi Portofolio",
        body: "Dua keluarga produk dengan karakter berbeda — camilan modern dan minuman tradisional — siap dipresentasikan untuk penjajakan pasar.",
      },
      {
        title: "Presentasi Kemasan",
        body: "Kemasan ziplock dan botol yang dirancang rapi, dengan ruang diskusi untuk penyesuaian label sesuai kebutuhan mitra.",
      },
      {
        title: "Peluang Kolaborasi",
        body: "Terbuka untuk kerja sama distribusi, penempatan ritel, dan bentuk kemitraan lain yang dirancang bersama.",
      },
      {
        title: "Diskusi Ekspor",
        body: "Percakapan eksplorasi untuk kebutuhan pasar luar negeri — mulai dari sampel, spesifikasi, hingga skenario kerja sama.",
      },
    ],
    ctaTitle: "Mari diskusikan peluang untuk pasar Anda.",
    ctaBody: "Sampaikan kebutuhan Anda — kami siapkan materi produk dan jadwal diskusi.",
    ctaButton: "Mulai Percakapan",
    pageIntro:
      "CV Gama Putra Santosa telah berpengalaman di jaringan retail dan modern trade Indonesia sejak 2011, dan kini membuka percakapan ekspor ke mancanegara. Produk kami terdaftar BPOM RI, ber-P-IRT, dan bersertifikat Halal, dokumen yang siap mendukung kebutuhan pasar tujuan. Halaman ini dirancang untuk mitra bisnis: distributor, peritel, dan calon buyer yang ingin mengenal portofolio kami lebih dekat. Kami tidak mengumbar klaim. Kami mengundang percakapan.",
    processTitle: "Bagaimana percakapan dimulai",
    process: [
      { title: "Perkenalan", body: "Ceritakan pasar dan kebutuhan Anda melalui formulir atau email." },
      { title: "Materi Produk", body: "Kami kirimkan profil produk, spesifikasi kemasan, dan daftar varian." },
      { title: "Sampel & Diskusi", body: "Penjajakan sampel dan diskusi skema kerja sama yang sesuai." },
      { title: "Kemitraan", body: "Kesepakatan dirancang bersama, bertumbuh secara bertahap." },
    ],
  },
  newsSection: {
    kicker: "Berita & Kegiatan",
    title: "Cerita dari dapur dan lapangan.",
    subtitle:
      "Sorotan produk, kegiatan perusahaan, partisipasi lokal, dan edukasi seputar bahan — didokumentasikan sebagai catatan perjalanan.",
    empty: "Belum ada artikel.",
  },
  contactSection: {
    kicker: "Hubungi Kami",
    title: "Mari mulai percakapan.",
    subtitle:
      "Untuk pertanyaan produk, distribusi, ekspor, maupun kolaborasi — tim kami siap membantu.",
    ctaTitle: "Punya pertanyaan atau peluang kerja sama?",
    ctaBody: "Satu pesan singkat sudah cukup untuk memulai.",
    ctaButton: "Hubungi Kami",
    mapTitle: "Peta lokasi",
    form: {
      name: "Nama",
      namePlaceholder: "Nama lengkap Anda",
      email: "Email",
      emailPlaceholder: "nama@perusahaan.com",
      topic: "Kategori",
      topics: ["Umum", "Produk", "Ekspor", "Kolaborasi"],
      message: "Pesan",
      messagePlaceholder: "Ceritakan kebutuhan Anda…",
      submit: "Kirim Pesan",
      sent: "Terima kasih — pesan Anda telah kami terima.",
    },
    info: {
      addressLabel: "Alamat",
      emailLabel: "Email",
      whatsappLabel: "WhatsApp",
      hoursLabel: "Jam Operasional",
    },
  },
  productsPage: {
    kicker: "Produk Kami",
    title: "Dua keluarga produk, satu standar dapur.",
    subtitle:
      "Kuicip dan Putri Teko adalah dua merek unggulan GPS Group, perusahaan makanan & minuman Yogyakarta sejak 2011, lahir dari dapur produksi yang sama di Sleman dan masing-masing membawa dunia rasanya sendiri.",
    highlightsLabel: "Sorotan",
  },
  kuicipPage: {
    kicker: "Keluarga Produk · Kuicip",
    title: "Singkong lokal, delapan kepribadian rasa.",
    subtitle:
      "Keripik singkong modern dalam kemasan ziplock 70 gr — diiris tipis, digoreng renyah, dan dibumbui dengan spektrum rasa dari yang jujur sampai yang berani.",
    catalogTitle: "Katalog Rasa",
    personalityLabel: "Kepribadian",
  },
  tekoPage: {
    kicker: "Keluarga Produk · Putri Teko",
    title: "Minuman tradisional, diracik untuk hari ini.",
    subtitle:
      "Dari jamu botol siap minum hingga racikan wedang uwuh — rempah asli hadir dalam lima bentuk kemasan: botol, kotak sachet, toples, kemasan praktis, dan besek anyaman untuk oleh-oleh.",
    rtdTitle: "Botol · Siap Minum",
    rtdBody: "Jamu klasik yang diseduh di dapur kami — tinggal buka botolnya, nikmat hangat maupun dingin.",
    brewTitle: "Seduh Sendiri",
    brewBody:
      "Empat bentuk kemasan untuk yang menikmati ritual menyeduh: kotak sachet praktis, toples racikan untuk stok dapur, kemasan rempah lengkap, dan besek anyaman untuk oleh-oleh.",
    packs: {
      kotak: {
        title: "Kotak Sachet",
        body: "Takaran pas sekali seduh dalam kotak batik khas.",
      },
      toples: {
        title: "Toples Racikan",
        body: "Racikan siap seduh untuk stok dapur — ambil sesuai selera.",
      },
      kemasan: {
        title: "Kemasan Praktis",
        body: "Rempah utuh lengkap dengan gula batu dalam satu kemasan.",
      },
      besek: {
        title: "Besek · Oleh-Oleh Khas",
        body: "Racikan yang sama, dikemas dalam besek anyaman bambu — bentuk oleh-oleh yang unik dan berkesan.",
      },
    },
    servingLabel: "Penyajian",
    groupNote: "Detail varian dan takaran dapat disesuaikan dengan data produk final.",
  },
  productDetail: {
    categoryLabel: "Kategori",
    aboutTitle: "Tentang produk ini",
    highlightsTitle: "Keunggulan",
    notesTitleKuicip: "Catatan rasa",
    notesTitleTeko: "Bahan & penyajian",
    relatedTitle: "Produk lain dari keluarga ini",
    inquiryTitle: "Tertarik dengan produk ini?",
    inquiryBody: "Hubungi kami untuk info ketersediaan, kerja sama distribusi, atau permintaan sampel.",
    inquiryButton: "Tanyakan Produk Ini",
    weightLabel: "Isi bersih",
  },
  articleDetail: {
    publishedLabel: "Dipublikasikan",
    shareHint: "Bagikan artikel ini",
  },
  footer: {
    tagline: "Perusahaan makanan & minuman dari Sleman, Yogyakarta, berdiri sejak 2011. Rumah bagi Ngayogyakarya, Putri Teko, dan Kuicip.",
    linksTitle: "Navigasi",
    brandsTitle: "Keluarga Produk",
    contactTitle: "Kontak",
    langTitle: "Bahasa",
    copyright: "Hak cipta dilindungi.",
  },
};

const en: typeof id = {
  nav: {
    home: "Home",
    products: "Our Products",
    export: "Export",
    news: "News & Activities",
    contact: "Contact Us",
    inquiry: "Contact Us",
    partners: "Partners",
  },
  common: {
    seeAll: "See all",
    seeAllProducts: "See all products",
    readMore: "Read more",
    backToNews: "Back to News & Activities",
    backToProducts: "Back to catalog",
    exploreBrand: "Explore",
    viewDetail: "View details",
    home: "Home",
    relatedArticles: "Related articles",
    editableNote: "Placeholder content — replace with final data.",
  },
  packaging: {
    botol: "Bottle",
    kotak: "Sachet Box",
    toples: "Jar",
    kemasan: "Pack",
    besek: "Gift Basket",
  },
  hero: {
    kicker: "Gama Putra Santosa · Sleman, Yogyakarta",
    title: "Think Global, Act Local",
    subtitle:
      "The crunch of Kuicip and the warmth of Putri Teko are flagship brands of GPS Group, a Yogyakarta food & beverage company since 2011.",
    ctaProducts: "Explore Products",
    ctaAbout: "About Us",
    heroBadgeKuicipLabel: "Kuicip Variants",
    heroBadgeTekoLabel: "Putri Teko Blends",
  },
  intro: {
    kicker: "About Us",
    title: "One of Yogyakarta's leading food & beverage companies, since 2011.",
    body: "CV Gama Putra Santosa (GPS Group) has been based in Sleman, Yogyakarta since 2011. We're home to three brands, namely Ngayogyakarya, Putri Teko, and Kuicip, spanning traditional beverages, botanical spice drinks, and snacks, marketed through retail and modern trade networks across Indonesia, with a growing vision toward international markets. We carry this journey with integrity, an entrepreneurial spirit, and continuous innovation, always striving to be the best at what we do.",
    facts: [
      { label: "Founded", value: "2011" },
      { label: "Production Base", value: "Sleman, Yogyakarta" },
      { label: "Home to", value: "Ngayogyakarya · Putri Teko · Kuicip" },
      { label: "Reach", value: "National Retail & Modern Trade" },
      { label: "Certification", value: "BPOM RI · P-IRT · Halal" },
    ],
  },
  featured: {
    kicker: "Featured Products",
    title: "Highlights from Our Two Flavor Worlds",
  },
  partnersSection: {
    kicker: "Our Partners",
    title: "Trusted by retail, hotel, and culinary partners.",
  },
  partnersPage: {
    kicker: "Partners",
    title: "Our partner network across Indonesia.",
    subtitle: "From national retail chains to government institutions — here are the partners who trust our products.",
    all: "All",
    categories: {
      ritel: "Retail",
      hotel: "Hotel",
      restaurant: "Restaurant",
      "oleh-oleh": "Souvenir Shops",
      cakery: "Cakery",
      institusi: "Institutions",
    },
    empty: "No partners in this category yet.",
  },
  families: {
    kuicipTagline: "Crunchy, bold, and addictive — cassava chips in 8 distinctive Indonesian flavors.",
    kuicipCta: "Explore Kuicip",
    tekoTagline: "Warm ancestral blends — herbal, grounding, full of meaning.",
    tekoCta: "Explore Putri Teko",
    center: "1 Company, 2 Flavor Worlds",
  },
  homeExport: {
    kicker: "Export",
    title: "Building Global Export Partnerships",
    desc: "We welcome distribution and export partners who want to bring Yogyakarta's flavors to international markets.",
    cards: [
      {
        title: "Production Capacity",
        desc: "Mid-scale production facility in Sleman built to hygienic standards.",
      },
      {
        title: "Quality Standards",
        desc: "Production follows food-safety standards for domestic and export markets.",
      },
      {
        title: "Export-Ready Packaging",
        desc: "Packaging engineered for long transit and destination-market rules.",
      },
    ],
    panelTitle: "Interested in Becoming an Export Partner?",
    panelDesc: "Share your needs and our team will follow up within 2 business days.",
    fieldName: "Company Name",
    fieldEmail: "Business Email",
    fieldCountry: "Destination Country",
    panelCta: "Send Export Inquiry",
  },
  homeContact: {
    kicker: "Contact",
    title: "Let's Connect",
    emailCta: "Send email",
    pathways: [
      { title: "General Inquiry", desc: "General company information." },
      { title: "Product Inquiry", desc: "Ask about Kuicip & Putri Teko." },
      { title: "Export Partnership", desc: "Become our export distribution partner." },
      { title: "Collaboration", desc: "Other partnership opportunities." },
    ],
  },
  exportSection: {
    kicker: "Export & Partnership",
    title: "A portfolio open for exploration.",
    subtitle:
      "We welcome conversations with distributors, retailers, and prospective buyers — from product exploration to destination-market discussions.",
    blocks: [
      {
        title: "Portfolio Exploration",
        body: "Two product families with distinct characters — modern snacks and traditional beverages — ready to present for market exploration.",
      },
      {
        title: "Packaging Presentation",
        body: "Neatly designed ziplock and bottle packaging, with room to discuss label adjustments for partner needs.",
      },
      {
        title: "Collaboration Opportunities",
        body: "Open to distribution partnerships, retail placement, and other collaboration formats designed together.",
      },
      {
        title: "Export Discussions",
        body: "Exploratory conversations for overseas market needs — from samples and specifications to partnership scenarios.",
      },
    ],
    ctaTitle: "Let's discuss the opportunity for your market.",
    ctaBody: "Tell us what you need — we'll prepare product materials and a discussion schedule.",
    ctaButton: "Start the Conversation",
    pageIntro:
      "CV Gama Putra Santosa has built experience across Indonesia's retail and modern trade networks since 2011, and is now opening conversations for export. Our products are registered with Indonesia's BPOM, hold a P-IRT number, and are Halal-certified, documentation ready to support destination-market requirements. This page is built for business partners: distributors, retailers, and prospective buyers who want a closer look at our portfolio. We don't broadcast claims. We invite conversations.",
    processTitle: "How the conversation starts",
    process: [
      { title: "Introduction", body: "Tell us about your market and needs via the form or email." },
      { title: "Product Materials", body: "We send product profiles, packaging specifications, and the variant list." },
      { title: "Samples & Discussion", body: "Sample exploration and discussion of a suitable partnership scheme." },
      { title: "Partnership", body: "Agreements designed together, growing step by step." },
    ],
  },
  newsSection: {
    kicker: "News & Activities",
    title: "Stories from the kitchen and the field.",
    subtitle:
      "Product highlights, company activities, local participation, and ingredient education — documented as travel notes.",
    empty: "No articles yet.",
  },
  contactSection: {
    kicker: "Contact Us",
    title: "Let's start a conversation.",
    subtitle:
      "For product, distribution, export, or collaboration inquiries — our team is ready to help.",
    ctaTitle: "Have a question or a partnership opportunity?",
    ctaBody: "One short message is enough to get started.",
    ctaButton: "Contact Us",
    mapTitle: "Location map",
    form: {
      name: "Name",
      namePlaceholder: "Your full name",
      email: "Email",
      emailPlaceholder: "name@company.com",
      topic: "Category",
      topics: ["General", "Product", "Export", "Collaboration"],
      message: "Message",
      messagePlaceholder: "Tell us what you need…",
      submit: "Send Message",
      sent: "Thank you — we have received your message.",
    },
    info: {
      addressLabel: "Address",
      emailLabel: "Email",
      whatsappLabel: "WhatsApp",
      hoursLabel: "Business Hours",
    },
  },
  productsPage: {
    kicker: "Our Products",
    title: "Two product families, one kitchen standard.",
    subtitle:
      "Kuicip and Putri Teko are flagship brands of GPS Group, a Yogyakarta food & beverage company since 2011, born from the same production kitchen in Sleman and each carrying its own world of flavor.",
    highlightsLabel: "Highlights",
  },
  kuicipPage: {
    kicker: "Product Family · Kuicip",
    title: "Local cassava, eight flavor personalities.",
    subtitle:
      "Modern cassava chips in 70 gr ziplock packaging — sliced thin, fried crisp, and seasoned across a spectrum from honest to daring.",
    catalogTitle: "Flavor Catalog",
    personalityLabel: "Personality",
  },
  tekoPage: {
    kicker: "Product Family · Putri Teko",
    title: "Traditional beverages, crafted for today.",
    subtitle:
      "From ready-to-drink bottled jamu to Wedang Uwuh blends — real spices in five packaging forms: bottles, sachet boxes, jars, practical packs, and a woven gift basket.",
    rtdTitle: "Bottles · Ready to Drink",
    rtdBody: "Classic jamu brewed in our kitchen — just open the bottle, enjoy warm or chilled.",
    brewTitle: "Brew at Home",
    brewBody:
      "Four packaging forms for those who savor the brewing ritual: practical sachet boxes, jar blends for the pantry, complete spice packs, and a woven basket for gifting.",
    packs: {
      kotak: {
        title: "Sachet Boxes",
        body: "Measured single-steep portions in the signature batik box.",
      },
      toples: {
        title: "Jar Blends",
        body: "Ready-to-steep blends for the pantry — spoon out to taste.",
      },
      kemasan: {
        title: "Practical Packs",
        body: "Whole spices complete with rock sugar in one pack.",
      },
      besek: {
        title: "Besek · Signature Souvenir",
        body: "The same blend, packaged in a hand-woven bamboo basket — a unique, memorable gift form.",
      },
    },
    servingLabel: "Serving",
    groupNote: "Variant details and portions can be aligned with final product data.",
  },
  productDetail: {
    categoryLabel: "Category",
    aboutTitle: "About this product",
    highlightsTitle: "Highlights",
    notesTitleKuicip: "Flavor notes",
    notesTitleTeko: "Ingredients & serving",
    relatedTitle: "More from this family",
    inquiryTitle: "Interested in this product?",
    inquiryBody: "Contact us for availability, distribution partnerships, or sample requests.",
    inquiryButton: "Ask About This Product",
    weightLabel: "Net weight",
  },
  articleDetail: {
    publishedLabel: "Published",
    shareHint: "Share this article",
  },
  footer: {
    tagline: "A food & beverage company from Sleman, Yogyakarta, founded in 2011. Home to Ngayogyakarya, Putri Teko, and Kuicip.",
    linksTitle: "Navigation",
    brandsTitle: "Product Families",
    contactTitle: "Contact",
    langTitle: "Language",
    copyright: "All rights reserved.",
  },
};

export const dictionary = { id, en } as const;
export type Dictionary = typeof id;

type LangContextValue = {
  lang: Lang;
  setLang: (lang: Lang) => void;
  t: Dictionary;
};

const LangContext = createContext<LangContextValue | null>(null);

export function LanguageProvider({ children }: { children: ReactNode }) {
  const [lang, setLang] = useState<Lang>("id");
  return (
    <LangContext.Provider value={{ lang, setLang, t: dictionary[lang] }}>
      {children}
    </LangContext.Provider>
  );
}

export function useLang(): LangContextValue {
  const ctx = useContext(LangContext);
  if (!ctx) throw new Error("useLang must be used inside <LanguageProvider>");
  return ctx;
}

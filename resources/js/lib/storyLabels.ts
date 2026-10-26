import type { Lang } from "@/lib/i18n";

const categories: Record<Lang, Record<string, string>> = {
  id: { kehidupan: "Kehidupan", percintaan: "Percintaan", pertemanan: "Pertemanan", bahagia: "Bahagia", random: "Random" },
  en: { kehidupan: "Life", percintaan: "Love", pertemanan: "Friendship", bahagia: "Joy", random: "Random" },
};
export const storyStatusLabels: Record<string, string> = { pending: "Menunggu tinjauan", approved: "Disetujui", rejected: "Ditolak", hidden: "Disembunyikan" };
export const storyVisibilityLabels: Record<string, string> = { public: "Publik", private: "Privat" };
export function storyCategoryName(category: string, lang: Lang = "id") {
  return categories[lang][category] ?? category;
}

import { useLang } from "@/lib/i18n";

export function StoryRemovalHelp({ email }: { email: string }) {
  const { lang } = useLang();
  return <div className="space-y-2 text-sm leading-relaxed text-espresso-700">
    <p>{lang === "id"
      ? "Untuk mengajukan penghapusan cerita, hubungi tim GPS FOOD melalui kontak resmi dan sertakan kode cerita sebagai referensi. Tim akan meninjau permintaan untuk melindungi privasi dan mencegah penyalahgunaan."
      : "To request story removal, contact the GPS FOOD team through the official contact channel and include the story code as a reference. The team will review the request to protect privacy and prevent misuse."}{" "}
      <a href={`mailto:${email}`} className="break-words font-semibold underline underline-offset-2">{email}</a>
    </p>
    <p className="text-xs text-espresso-600">{lang === "id"
      ? "Kode cerita adalah referensi, bukan bukti kepemilikan. Permintaan tidak dipenuhi secara otomatis."
      : "A story code is a reference, not proof of ownership. Requests are not fulfilled automatically."}</p>
  </div>;
}

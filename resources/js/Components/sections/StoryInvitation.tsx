import { Link } from "@inertiajs/react";
import { ArrowUpRight } from "lucide-react";
import { useLang } from "@/lib/i18n";
export function StoryInvitation() {
  const { lang } = useLang(); const id = lang === "id";
  return <section className="container-page py-12"><div className="relative overflow-hidden rounded-[2rem] bg-gold-300 px-7 py-12 sm:p-12"><div className="grid items-center gap-8 md:grid-cols-[1.4fr_1fr]"><div><p className="kicker !text-espresso-800">Kuicip · Teman Ceritamu</p><h2 className="mt-4 max-w-lg font-display text-3xl sm:text-4xl">{id ? "Bukan cuma rasa. Ada cerita juga." : "More than a flavour. A little room for your story."}</h2></div><div><p className="max-w-md leading-relaxed text-espresso-800">{id ? "Yang ringan, yang hangat, sampai yang belum sempat terucap. Kenali Ruang Cerita bersama Kuicip." : "The little joys, the big feelings, the things you have not said yet. Discover Ruang Cerita with Kuicip."}</p><Link href="/kuicip/cerita" className="btn-dark mt-6">{id ? "Kenali Ruang Cerita" : "Discover Ruang Cerita"}<ArrowUpRight size={18}/></Link></div></div></div></section>;
}

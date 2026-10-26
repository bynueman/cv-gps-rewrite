import { Link } from "@inertiajs/react";
import { ArrowUpRight } from "lucide-react";
import { useLang } from "@/lib/i18n";

export function StoryInvitation() {
  const { t } = useLang();
  return (
    <section aria-labelledby="story-invitation-title" className="container-page py-12 sm:py-16">
      <div className="overflow-hidden rounded-[2rem] border border-espresso-900/10 bg-cream-50 p-6 sm:p-10 lg:p-12">
        <div className="grid items-center gap-8 md:grid-cols-[1.4fr_0.8fr] lg:gap-12">
          <div className="min-w-0">
            <p className="kicker !text-gold-700">{t.homeStory.kicker}</p>
            <h2 id="story-invitation-title" className="mt-4 max-w-xl font-display text-3xl leading-[1.12] text-espresso-900 sm:text-4xl lg:text-5xl">
              {t.homeStory.title}
            </h2>
            <p className="mt-5 max-w-xl leading-relaxed text-espresso-700">{t.homeStory.description}</p>
            <Link href="/kuicip/cerita" className="btn-dark mt-6 min-h-12">
              {t.homeStory.cta}<ArrowUpRight size={18} aria-hidden="true"/>
            </Link>
            <p className="mt-4 text-xs leading-relaxed text-espresso-600 sm:text-sm">{t.homeStory.helper}</p>
          </div>
          <svg viewBox="0 0 320 250" fill="none" aria-hidden="true" focusable="false" className="pointer-events-none mx-auto hidden w-full max-w-xs md:block">
            <path d="M20 216C97 225 120 185 159 130S232 30 300 29" className="stroke-gold-700/30" strokeWidth="1.5"/>
            <path d="M48 31H238A24 24 0 0 1 262 55V128A24 24 0 0 1 238 152H102L61 181V152H48A24 24 0 0 1 24 128V55A24 24 0 0 1 48 31Z" className="fill-cream-100 stroke-espresso-900/20" strokeWidth="1.5"/>
            <path d="M79 92C79 76 85 68 98 64V75C91 78 89 83 89 89H102V111H79V92ZM117 92C117 76 123 68 136 64V75C129 78 127 83 127 89H140V111H117V92Z" className="fill-gold-700/70"/>
            <path d="M176 133H275A21 21 0 0 1 296 154V185A21 21 0 0 1 275 206H263V226L235 206H176A21 21 0 0 1 155 185V154A21 21 0 0 1 176 133Z" className="fill-gold-300/30 stroke-gold-700/40" strokeWidth="1.5"/>
            <g className="fill-espresso-700"><circle cx="201" cy="170" r="4"/><circle cx="225" cy="170" r="4"/><circle cx="249" cy="170" r="4"/></g>
            <path d="M283 66V86M273 76H293M63 210V222M57 216H69" className="stroke-gold-700/60" strokeWidth="2" strokeLinecap="round"/>
          </svg>
        </div>
      </div>
    </section>
  );
}

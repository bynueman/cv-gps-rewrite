import type { ReactElement } from "react";
import { Head, usePage } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";
import { useReveal } from "@/hooks/useReveal";
import { PageHeader } from "@/Components/PageHeader";
import { ContactForm } from "@/Components/ContactForm";
import SiteLayout from "@/Layouts/SiteLayout";
import type { SharedProps } from "@/types";

type Seo = { title: string; description: string; canonical?: string };

/** Contact page: full inquiry form, company info, and a map slot. */
function Contact({ seo }: { seo: Seo }) {
  const { lang, t } = useLang();
  const scope = useReveal<HTMLElement>([lang]);
  const { company } = usePage<SharedProps>().props.site;
  const info = t.contactSection.info;

  return (
    <>
      <Head title={seo.title}>
        <meta name="description" content={seo.description} />
        {seo.canonical ? <link rel="canonical" href={seo.canonical} /> : null}
      </Head>
      <PageHeader
        kicker={t.contactSection.kicker}
        title={t.contactSection.title}
        lede={t.contactSection.subtitle}
        deps={[lang]}
      />

      <section ref={scope} className="pb-20 lg:pb-28">
        <div className="container-page grid gap-12 lg:grid-cols-12 lg:gap-16">
          <div data-reveal className="lg:col-span-7">
            <ContactForm />
          </div>

          <div className="space-y-6 lg:col-span-5">
            <div data-reveal className="rounded-3xl border border-espresso-900/10 bg-cream-50 p-8">
              <h2 className="font-display text-xl font-semibold">{company.name}</h2>
              <dl className="mt-6 space-y-5 text-sm">
                <div>
                  <dt className="text-xs font-semibold uppercase tracking-[0.15em] text-espresso-500">
                    {info.addressLabel}
                  </dt>
                  <dd className="mt-1 leading-relaxed text-espresso-700">{company.address}</dd>
                </div>
                <div>
                  <dt className="text-xs font-semibold uppercase tracking-[0.15em] text-espresso-500">
                    {info.emailLabel}
                  </dt>
                  <dd className="mt-1 space-y-0.5">
                    <a href={`mailto:${company.email}`} className="block text-espresso-700 hover:text-gold-700">
                      {company.email}
                    </a>
                    {company.email_alt && (
                      <a href={`mailto:${company.email_alt}`} className="block text-espresso-700 hover:text-gold-700">
                        {company.email_alt}
                      </a>
                    )}
                  </dd>
                </div>
                <div>
                  <dt className="text-xs font-semibold uppercase tracking-[0.15em] text-espresso-500">
                    {info.whatsappLabel}
                  </dt>
                  <dd className="mt-1">
                    <a href={company.whatsapp_href} className="text-espresso-700 hover:text-gold-700">
                      {company.whatsapp}
                    </a>
                  </dd>
                </div>
                <div>
                  <dt className="text-xs font-semibold uppercase tracking-[0.15em] text-espresso-500">
                    {info.hoursLabel}
                  </dt>
                  <dd className="mt-1 text-espresso-700">{company.hours[lang]}</dd>
                </div>
              </dl>
              <a href={company.whatsapp_href} className="btn-primary mt-8 w-full">
                WhatsApp →
              </a>
            </div>

            <div data-reveal className="overflow-hidden rounded-3xl border border-espresso-900/10">
              <iframe
                src={company.map_embed_url}
                width="100%"
                height="320"
                style={{ border: 0 }}
                loading="lazy"
                referrerPolicy="strict-origin-when-cross-origin"
                title={`${t.contactSection.mapTitle} ${company.name}`}
                className="block aspect-[4/3] w-full"
              />
            </div>
          </div>
        </div>
      </section>
    </>
  );
}

Contact.layout = (page: ReactElement) => <SiteLayout children={page} />;

export default Contact;

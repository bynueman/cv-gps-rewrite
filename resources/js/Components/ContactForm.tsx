import { useState, type FormEvent } from "react";
import { useLang } from "@/lib/i18n";

const inputClass =
  "w-full rounded-xl border border-espresso-900/15 bg-cream-50 px-4 py-3 text-sm text-espresso-900 placeholder:text-espresso-500/60 outline-none transition-colors focus:border-gold-500 focus:ring-2 focus:ring-gold-400/30";

/**
 * Inquiry form with category select (General / Product / Export /
 * Collaboration). No backend yet — matches the original app (this was
 * never wired to a real submit endpoint there either).
 */
export function ContactForm({ defaultTopicIndex = 0 }: { defaultTopicIndex?: number }) {
  const { t } = useLang();
  const [sent, setSent] = useState(false);

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setSent(true);
  }

  const f = t.contactSection.form;

  return (
    <form onSubmit={handleSubmit}>
      <div className="grid gap-5 sm:grid-cols-2">
        <label className="block">
          <span className="mb-1.5 block text-sm font-semibold">{f.name}</span>
          <input type="text" name="name" required placeholder={f.namePlaceholder} className={inputClass} />
        </label>
        <label className="block">
          <span className="mb-1.5 block text-sm font-semibold">{f.email}</span>
          <input type="email" name="email" required placeholder={f.emailPlaceholder} className={inputClass} />
        </label>
      </div>
      <label className="mt-5 block">
        <span className="mb-1.5 block text-sm font-semibold">{f.topic}</span>
        <select name="topic" className={inputClass} defaultValue={f.topics[defaultTopicIndex]}>
          {f.topics.map((topic) => (
            <option key={topic} value={topic}>
              {topic}
            </option>
          ))}
        </select>
      </label>
      <label className="mt-5 block">
        <span className="mb-1.5 block text-sm font-semibold">{f.message}</span>
        <textarea name="message" required rows={5} placeholder={f.messagePlaceholder} className={inputClass} />
      </label>
      <div className="mt-6 flex flex-wrap items-center gap-4">
        <button type="submit" className="btn-dark">
          {f.submit}
        </button>
        {sent ? (
          <p role="status" className="text-sm font-medium text-gold-700">
            {f.sent}
          </p>
        ) : null}
      </div>
    </form>
  );
}

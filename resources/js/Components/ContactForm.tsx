import { useEffect, type FormEvent } from "react";
import { useForm } from "@inertiajs/react";
import { useLang } from "@/lib/i18n";

const inputClass =
  "w-full rounded-xl border border-espresso-900/15 bg-cream-50 px-4 py-3 text-sm text-espresso-900 placeholder:text-espresso-500/60 outline-none transition-colors focus:border-gold-500 focus:ring-2 focus:ring-gold-400/30";

/** Inquiry form with category select (General / Product / Export / Collaboration). */
export function ContactForm({ defaultTopicIndex = 0 }: { defaultTopicIndex?: number }) {
  const { t } = useLang();
  const f = t.contactSection.form;

  const { data, setData, post, processing, errors, recentlySuccessful, reset } = useForm({
    name: "",
    email: "",
    topic: f.topics[defaultTopicIndex],
    message: "",
  });

  // Keep the selected topic valid when the language (and thus the topic
  // label strings) changes, without clobbering a user's in-progress pick.
  useEffect(() => {
    if (!f.topics.includes(data.topic)) {
      setData("topic", f.topics[defaultTopicIndex]);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [f.topics]);

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    post(route("contact.store"), {
      preserveScroll: true,
      onSuccess: () => reset("name", "email", "message"),
    });
  }

  return (
    <form onSubmit={handleSubmit}>
      <div className="grid gap-5 sm:grid-cols-2">
        <label className="block">
          <span className="mb-1.5 block text-sm font-semibold">{f.name}</span>
          <input
            type="text"
            required
            placeholder={f.namePlaceholder}
            className={inputClass}
            value={data.name}
            onChange={(e) => setData("name", e.target.value)}
          />
          {errors.name ? <p className="mt-1.5 text-sm text-red-700">{errors.name}</p> : null}
        </label>
        <label className="block">
          <span className="mb-1.5 block text-sm font-semibold">{f.email}</span>
          <input
            type="email"
            required
            placeholder={f.emailPlaceholder}
            className={inputClass}
            value={data.email}
            onChange={(e) => setData("email", e.target.value)}
          />
          {errors.email ? <p className="mt-1.5 text-sm text-red-700">{errors.email}</p> : null}
        </label>
      </div>
      <label className="mt-5 block">
        <span className="mb-1.5 block text-sm font-semibold">{f.topic}</span>
        <select
          className={inputClass}
          value={data.topic}
          onChange={(e) => setData("topic", e.target.value)}
        >
          {f.topics.map((topic) => (
            <option key={topic} value={topic}>
              {topic}
            </option>
          ))}
        </select>
      </label>
      <label className="mt-5 block">
        <span className="mb-1.5 block text-sm font-semibold">{f.message}</span>
        <textarea
          required
          rows={5}
          placeholder={f.messagePlaceholder}
          className={inputClass}
          value={data.message}
          onChange={(e) => setData("message", e.target.value)}
        />
        {errors.message ? <p className="mt-1.5 text-sm text-red-700">{errors.message}</p> : null}
      </label>
      <div className="mt-6 flex flex-wrap items-center gap-4">
        <button type="submit" disabled={processing} className="btn-dark disabled:opacity-60">
          {f.submit}
        </button>
        {recentlySuccessful ? (
          <p role="status" className="text-sm font-medium text-gold-700">
            {f.sent}
          </p>
        ) : null}
      </div>
    </form>
  );
}

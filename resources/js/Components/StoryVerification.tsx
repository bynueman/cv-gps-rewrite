import { useCallback, useEffect, useRef, useState } from "react";
import { useLang } from "@/lib/i18n";

declare global {
  interface Window {
    turnstile?: {
      render: (element: HTMLElement, options: Record<string, unknown>) => string;
      remove: (id: string) => void;
      reset: (id: string) => void;
    };
  }
}
export type StoryVerificationValue = { token: string; answer: string };
type Challenge = { token: string; question: string; expires_in: number; wait_seconds: number };

export function StoryVerification({ provider, siteKey, localPreview, resetKey, onChange }: {
  provider: string; siteKey: string | null; localPreview: boolean; resetKey: number;
  onChange: (value: StoryVerificationValue) => void;
}) {
  const { lang } = useLang();
  const id = lang === "id";
  const [challenge, setChallenge] = useState<Challenge | null>(null);
  const [answer, setAnswer] = useState("");
  const [ready, setReady] = useState(false);
  const [expired, setExpired] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const request = useRef<AbortController | null>(null);
  const widget = useRef<HTMLDivElement>(null);
  const widgetId = useRef<string | null>(null);

  const renew = useCallback(async () => {
    request.current?.abort();
    const abort = new AbortController();
    request.current = abort;
    setLoading(true); setError(""); setChallenge(null); setAnswer(""); setReady(false); setExpired(false);
    onChange({ token: "", answer: "" });
    try {
      const response = await window.axios.post<Challenge>(route("stories.challenge"), {}, { signal: abort.signal, timeout: 8000 });
      if (!abort.signal.aborted) setChallenge(response.data);
    } catch (failure: any) {
      if (!abort.signal.aborted) setError(failure?.response?.status === 429
        ? (id ? "Terlalu banyak permintaan soal. Tunggu satu menit, lalu coba lagi." : "Too many requests. Wait a minute, then try again.")
        : (id ? "Soal belum dapat dimuat. Silakan coba lagi." : "The question could not be loaded. Please try again."));
    } finally {
      if (!abort.signal.aborted) setLoading(false);
    }
  }, [id, onChange]);

  useEffect(() => {
    if (provider !== "local") return;
    void renew();
    return () => request.current?.abort();
  }, [provider, resetKey, renew]);

  useEffect(() => {
    if (!challenge) return;
    const wait = window.setTimeout(() => setReady(true), challenge.wait_seconds * 1000);
    const expiry = window.setTimeout(() => { setExpired(true); setReady(false); }, challenge.expires_in * 1000);
    return () => { window.clearTimeout(wait); window.clearTimeout(expiry); };
  }, [challenge]);

  useEffect(() => {
    if (provider === "local") onChange({ token: ready && !expired ? challenge?.token ?? "" : "", answer });
  }, [provider, challenge, answer, ready, expired, onChange]);

  useEffect(() => {
    if (provider !== "turnstile") return;
    onChange({ token: "", answer: "" });
    if (localPreview) {
      onChange({ token: "gpsfood-local-preview-token", answer: "" });
      return;
    }
    if (!siteKey) return;
    let cancelled = false;
    const render = () => {
      if (!cancelled && widget.current && window.turnstile && widgetId.current === null) {
        widgetId.current = window.turnstile.render(widget.current, {
          sitekey: siteKey, action: "story_submit",
          callback: (token: string) => onChange({ token, answer: "" }),
          "expired-callback": () => onChange({ token: "", answer: "" }),
          "error-callback": () => onChange({ token: "", answer: "" }),
        });
      }
    };
    let script = document.querySelector<HTMLScriptElement>("script[data-story-turnstile]");
    if (!script) {
      script = document.createElement("script");
      script.src = "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit";
      script.async = true;
      script.dataset.storyTurnstile = "true";
      document.head.appendChild(script);
    }
    script.addEventListener("load", render); render();
    return () => {
      cancelled = true; script.removeEventListener("load", render);
      if (widgetId.current && window.turnstile) window.turnstile.remove(widgetId.current);
      widgetId.current = null;
    };
  }, [provider, siteKey, localPreview, resetKey, onChange]);

  if (provider === "turnstile") return <><div ref={widget} />{!siteKey && !localPreview && <p role="status" className="text-sm">{id ? "Verifikasi belum tersedia." : "Verification is unavailable."}</p>}</>;
  if (provider !== "local") return <p role="alert" className="text-sm">{id ? "Verifikasi belum tersedia. Coba lagi nanti." : "Verification is unavailable. Please try later."}</p>;

  return (
    <fieldset className="rounded-2xl border border-espresso-900/15 bg-cream-100/70 px-4 pb-4 pt-2" aria-describedby="story-verification-help">
      <legend className="px-1 text-sm font-semibold">{id ? "Verifikasi sederhana" : "Quick verification"}</legend>
      <div className="grid grid-cols-[1fr_6rem] items-end gap-4"><label htmlFor="story-challenge-answer" className="block text-xs font-medium text-espresso-700">
        {id ? "Hitung dan tulis hasilnya" : "Calculate and enter the answer"}
        <span className="mt-2 block text-lg font-semibold tabular-nums text-espresso-900" aria-live="polite">{challenge ? challenge.question + " = ?" : (loading ? (id ? "Memuat soal…" : "Loading…") : "—")}</span>
      </label>
      <input id="story-challenge-answer" name="challenge_answer" type="text" inputMode="numeric" pattern="[0-9]{1,3}"
        autoComplete="off" maxLength={3} required disabled={!challenge || expired || loading} value={answer}
        onChange={(event) => setAnswer(event.target.value)}
        className="w-full rounded-xl border border-espresso-900/25 bg-cream-50 px-3 py-3 text-center text-base tabular-nums focus-visible:outline-espresso-700 disabled:opacity-50" /></div>
      <p id="story-verification-help" role="status" className="mt-3 text-xs leading-relaxed text-espresso-700">
        {expired ? (id ? "Soal kedaluwarsa. Minta soal baru; ceritamu tetap tersimpan di formulir." : "This question expired. Request another; your story stays in the form.")
          : (id ? "Soal berlaku 5 menit. Jika baru mengganti soal, tunggu sebentar sebelum mengirim." : "Valid for 5 minutes. Allow a moment after changing the question before sending.")}
      </p>
      <button type="button" onClick={() => void renew()} disabled={loading} className="mt-3 min-h-11 rounded-full border border-espresso-900/20 bg-cream-50 px-4 py-2 text-xs font-semibold transition-colors hover:bg-cream-200 disabled:opacity-50">
        {id ? "Minta soal baru" : "Get a new question"}
      </button>
      {error && <p role="alert" className="mt-3 text-sm text-red-800">{error}</p>}
    </fieldset>
  );
}

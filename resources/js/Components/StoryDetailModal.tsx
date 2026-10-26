import { storyCategoryName } from "@/lib/storyLabels";
import type { ReactNode } from "react";
import { Description, Dialog, DialogPanel, DialogTitle } from "@headlessui/react";
import { X } from "lucide-react";
import type { Lang } from "@/lib/i18n";
import type { PublicStory } from "@/types";

export function StoryText({ body, preview = false }: { body: string; preview?: boolean }) {
  return (
    <p className={
      "whitespace-pre-wrap [overflow-wrap:anywhere] text-base text-espresso-800 " +
      (preview ? "line-clamp-5 h-[7.5rem] leading-6" : "leading-7")
    }>
      {body}
    </p>
  );
}

export function StoryDetailModal({
  story, open, onClose, lang, children,
}: {
  story: Pick<PublicStory, "code" | "category" | "body">;
  open: boolean;
  onClose: () => void;
  lang: Lang;
  children: ReactNode;
}) {
  const id = lang === "id";
  return (
    <Dialog
      open={open}
      onClose={onClose}
      autoFocus
      transition
      className="relative z-[60] transition-opacity duration-150 data-[closed]:opacity-0 motion-reduce:transition-none"
    >
      <div className="fixed inset-0 bg-espresso-950/50" aria-hidden="true" />
      <div className="fixed inset-0 flex items-center justify-center p-4 sm:p-6">
        <DialogPanel className="flex max-h-[85vh] w-full min-w-0 max-w-2xl flex-col overflow-hidden rounded-3xl bg-cream-50 shadow-lift supports-[height:100dvh]:max-h-[85dvh]">
          <div className="flex shrink-0 items-start justify-between gap-4 border-b border-espresso-900/10 p-5 sm:px-7">
            <div className="min-w-0">
              <span className="rounded-full bg-gold-300/40 px-3 py-1 text-xs font-semibold capitalize">{storyCategoryName(story.category, lang)}</span>
              <DialogTitle className="mt-3 break-words font-display text-2xl text-espresso-950">
                {id ? "Cerita" : "Story"} {story.code}
              </DialogTitle>
              <Description className="sr-only">
                {id ? "Baca cerita lengkap. Tutup untuk kembali ke kumpulan cerita." : "Read the full story. Close to return to the story collection."}
              </Description>
            </div>
            <button
              type="button"
              data-autofocus
              onClick={onClose}
              aria-label={id ? "Tutup cerita" : "Close story"}
              className="grid h-11 w-11 shrink-0 place-items-center rounded-full border border-espresso-900/15 text-espresso-700 hover:bg-cream-200"
            >
              <X size={20} aria-hidden="true" />
            </button>
          </div>
          <div
            role="region"
            aria-label={id ? "Isi cerita" : "Story text"}
            tabIndex={0}
            className="min-h-0 overflow-y-auto overscroll-contain px-5 py-6 focus-visible:outline focus-visible:outline-2 focus-visible:outline-inset focus-visible:outline-gold-700 sm:px-7"
          >
            <StoryText body={story.body} />
          </div>
          <div className="shrink-0 border-t border-espresso-900/10 px-5 py-4 sm:px-7">{children}</div>
        </DialogPanel>
      </div>
    </Dialog>
  );
}

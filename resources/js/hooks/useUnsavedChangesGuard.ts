import { useEffect, useRef } from "react";
import { router } from "@inertiajs/react";

const DEFAULT_MESSAGE = "Perubahan belum disimpan. Yakin ingin meninggalkan halaman ini?";

/**
 * Warns before leaving a form with unsaved edits — both a full page
 * unload/refresh (native beforeunload, no bypass needed since Inertia
 * form submits never trigger a real unload) and an in-app Inertia
 * navigation (e.g. clicking a sidebar link).
 *
 * Pass `useForm()`'s `isDirty` straight through. Call the returned
 * `bypassNext()` immediately before your own `post`/`patch`/`put` so the
 * save-and-redirect navigation it triggers isn't itself treated as
 * "leaving with unsaved changes."
 */
export function useUnsavedChangesGuard(isDirty: boolean, message: string = DEFAULT_MESSAGE) {
  const dirtyRef = useRef(isDirty);
  dirtyRef.current = isDirty;

  const bypassNextRef = useRef(false);

  useEffect(() => {
    function handleBeforeUnload(e: BeforeUnloadEvent) {
      if (!dirtyRef.current) return;
      e.preventDefault();
      e.returnValue = "";
    }
    window.addEventListener("beforeunload", handleBeforeUnload);
    return () => window.removeEventListener("beforeunload", handleBeforeUnload);
  }, []);

  useEffect(() => {
    return router.on("before", (event) => {
      if (bypassNextRef.current) {
        bypassNextRef.current = false;
        return;
      }
      if (dirtyRef.current && !window.confirm(message)) {
        event.preventDefault();
      }
    });
  }, [message]);

  return {
    bypassNext: () => {
      bypassNextRef.current = true;
    },
  };
}

import { useEffect } from "react";

/** Signals the Blade splash after the initial React commit; never remounts on visits. */
export function BrandLoader() {
  useEffect(() => {
    const frame = window.requestAnimationFrame(() => {
      window.dispatchEvent(new Event("gpsfood:ready"));
    });
    return () => window.cancelAnimationFrame(frame);
  }, []);

  return null;
}

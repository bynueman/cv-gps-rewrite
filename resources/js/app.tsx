import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createRoot } from "react-dom/client";

const appName = import.meta.env.VITE_APP_NAME || "CV Gama Putra Santosa";

createInertiaApp({
  // The " — CV Gama Putra Santosa" suffix is applied once, server-side,
  // by App\Support\Seo — every page's <Head title={seo.title}> already
  // arrives fully formatted, so this must pass it through unchanged
  // (templating again here would double the suffix).
  title: (title) => title || appName,
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.tsx`,
      import.meta.glob("./Pages/**/*.tsx")
    ),
  setup({ el, App, props }) {
    const root = createRoot(el);
    root.render(<App {...props} />);
  },
  progress: {
    color: "#E29A12",
  },
});

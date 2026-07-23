/// <reference types="vite/client" />

// Ziggy's @routes Blade directive injects a global `route()` function at
// runtime (resources/views/app.blade.php) — no npm package needed.
declare function route(name?: string, params?: unknown, absolute?: boolean): string;

// resources/js/bootstrap.js exposes the configured axios instance globally.
interface Window {
  axios: import("axios").AxiosInstance;
}

<link rel="preload" as="image" href="{{ asset(ltrim(config('brand_assets.logos.gps'), '/')) }}" fetchpriority="high">
<style id="gpsfood-splash-styles">
    /* Critical CSS: the initial splash must work before the Vite bundle arrives. */
    #gpsfood-splash {
        position: fixed;
        inset: 0;
        z-index: 2147483647;
        display: grid;
        place-items: center;
        overflow: hidden;
        background: #FFFFFF;
        opacity: 1;
        transition: opacity 220ms ease;
        animation: gpsfood-splash-safety 0s 5s forwards;
    }
    #gpsfood-splash[hidden] { display: none; }
    #gpsfood-splash.is-leaving { opacity: 0; pointer-events: none; }
    #gpsfood-splash img { width: 120px; height: auto; opacity: 0; transform: scale(0.94); }
    #gpsfood-splash img.logo-ready { animation: gpsfood-logo-reveal 280ms ease-out forwards; }
    @media (min-width: 768px) { #gpsfood-splash img { width: 150px; } }
    @keyframes gpsfood-logo-reveal { to { opacity: 1; transform: scale(1); } }
    @keyframes gpsfood-splash-safety { to { visibility: hidden; opacity: 0; pointer-events: none; } }
    @media (prefers-reduced-motion: reduce) {
        #gpsfood-splash { transition: none; }
        #gpsfood-splash img.logo-ready { animation: none; opacity: 1; transform: none; }
    }
</style>

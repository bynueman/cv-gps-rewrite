<div id="gpsfood-splash" aria-hidden="true" hidden>
    <img src="{{ asset(ltrim(config('brand_assets.logos.gps'), '/')) }}" width="3236" height="1080" alt="" fetchpriority="high" decoding="async">
</div>
<script id="gpsfood-splash-controller">
    (() => {
        const splash = document.getElementById('gpsfood-splash');
        const logo = splash.querySelector('img');
        const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
        let appReady = false;
        let logoReady = false;
        let closing = false;
        let shownAt = 0;
        let holdTimer;
        let removeTimer;

        function remove() {
            window.clearTimeout(safetyTimer);
            window.clearTimeout(holdTimer);
            window.clearTimeout(removeTimer);
            window.removeEventListener('gpsfood:ready', ready);
            logo.removeEventListener('load', loaded);
            logo.removeEventListener('error', loaded);
            splash.remove();
        }

        function dismissWhenReady() {
            if (!appReady || !logoReady || closing) return;
            closing = true;
            // Finish the reveal plus a brief hold; slow boot gets no extra branding delay.
            const hold = motion.matches || !logo.naturalWidth ? 0 : Math.max(0, 400 - (performance.now() - shownAt));
            holdTimer = window.setTimeout(() => {
                splash.classList.add('is-leaving');
                removeTimer = window.setTimeout(remove, motion.matches ? 0 : 220);
            }, hold);
        }

        function ready() {
            appReady = true;
            dismissWhenReady();
        }

        function loaded() {
            if (logoReady) return;
            logoReady = true;
            shownAt = performance.now();
            if (logo.naturalWidth) logo.classList.add('logo-ready');
            dismissWhenReady();
        }

        // Independent of React and image loading; CSS also releases the overlay after 5s.
        const safetyTimer = window.setTimeout(remove, 5000);
        window.addEventListener('gpsfood:ready', ready, { once: true });
        logo.addEventListener('load', loaded, { once: true });
        logo.addEventListener('error', loaded, { once: true });
        splash.hidden = false;
        if (logo.complete) loaded();
    })();
</script>

// Theme toggle
//
// The initial theme is applied pre-paint by a small inline <head> script (see
// the layout) to avoid a flash. This block handles the toggle button, live
// system-preference changes, and re-applying state across wire:navigate visits.
(function () {
    const root = document.documentElement;
    const media = window.matchMedia('(prefers-color-scheme: dark)');

    function resolveTheme() {
        const stored = localStorage.getItem('theme');
        if (stored === 'light' || stored === 'dark') {
            return stored;
        }
        return media.matches ? 'dark' : 'light';
    }

    function syncToggle() {
        const btn = document.querySelector('[data-theme-toggle]');
        if (!btn) { return; }
        const isDark = root.getAttribute('data-theme') === 'dark';
        btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        btn.innerHTML = isDark
            ? '<span aria-hidden="true">☀</span>'
            : '<span aria-hidden="true">◐</span>';
    }

    function applyTheme(theme) {
        root.setAttribute('data-theme', theme);
        // Mirror to a cookie so server-rendered (wire:navigate) pages match and
        // never flash the wrong theme.
        document.cookie = 'theme=' + theme + '; path=/; max-age=31536000; SameSite=Lax';
        syncToggle();
    }

    // Delegated so it keeps working after wire:navigate swaps the DOM.
    document.addEventListener('click', function (event) {
        if (!event.target.closest('[data-theme-toggle]')) { return; }
        const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', next);
        applyTheme(next);
    });

    // Follow the system theme only while the user hasn't chosen one explicitly.
    media.addEventListener('change', function () {
        if (! localStorage.getItem('theme')) {
            applyTheme(resolveTheme());
        }
    });

    // Re-assert the theme (and button state) after each SPA navigation.
    document.addEventListener('livewire:navigated', function () {
        applyTheme(resolveTheme());
    });

    syncToggle();
}());

// Scroll-reveal — re-run on first load and after every wire:navigate visit.
(function () {
    function initReveal() {
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const items = document.querySelectorAll('.reveal:not(.is-visible)');

        if (reducedMotion) {
            items.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        items.forEach(function (el) { observer.observe(el); });
    }

    document.addEventListener('DOMContentLoaded', initReveal);
    document.addEventListener('livewire:navigated', initReveal);
}());

// Visitor analytics — cookieless beacon. Fires only when a real browser runs
// JS, so bots that just fetch the HTML are never counted. The matching route is
// POST /beacon (CSRF-exempt); CV downloads are tracked server-side instead.
(function () {
    const BEACON_URL = '/beacon';

    function send(payload) {
        payload.path = location.pathname + location.search;
        payload.referrer = document.referrer || null;
        const body = JSON.stringify(payload);
        try {
            // sendBeacon survives the page unload (needed for duration).
            if (navigator.sendBeacon) {
                navigator.sendBeacon(BEACON_URL, new Blob([body], { type: 'application/json' }));
                return;
            }
        } catch (e) {}
        try {
            fetch(BEACON_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: body, keepalive: true, cache: 'no-store',
            }).catch(function () {});
        } catch (e) {}
    }

    let start = Date.now();
    let sentDuration = false;

    function sendDuration() {
        if (sentDuration) { return; }
        sentDuration = true;
        send({ event: 'duration', value: Math.round((Date.now() - start) / 1000) });
    }

    function trackPage() {
        // New page (initial load or SPA navigation): reset the duration timer.
        start = Date.now();
        sentDuration = false;

        send({ event: 'page_view' });

        // Section views — fire once per section the visitor scrolls to.
        try {
            const seen = {};
            const sections = ['top', 'projects', 'experiments', 'about', 'experience', 'contact'];
            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    const id = entry.target.id;
                    if (entry.isIntersecting && !seen[id]) {
                        seen[id] = true;
                        send({ event: 'section', label: id });
                    }
                });
            }, { threshold: 0.4 });
            sections.forEach(function (id) {
                const el = document.getElementById(id);
                if (el) { observer.observe(el); }
            });
        } catch (e) {}
    }

    // Key link / CTA clicks — delegated, bound once, survives SPA navigation.
    document.addEventListener('click', function (e) {
        const a = e.target.closest && e.target.closest('a[href]');
        if (!a) { return; }
        const href = (a.getAttribute('href') || '').toLowerCase();
        let label = null;
        if (href.indexOf('mailto:') === 0) { label = 'contact_email'; }
        else if (href.indexOf('tel:') === 0) { label = 'contact_phone'; }
        else if (href.indexOf('linkedin.') !== -1) { label = 'linkedin'; }
        else if (href.indexOf('github.') !== -1) { label = 'github'; }
        else if (href.slice(-9) === '#projects') { label = 'view_projects'; }
        // CV downloads are recorded server-side, so skip them here.
        if (label) { send({ event: 'click', label: label }); }
    }, true);

    // Time on page — sent once when the visitor leaves or navigates away.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') { sendDuration(); }
    });
    window.addEventListener('pagehide', sendDuration);
    // SPA navigation doesn't fire pagehide, so flush before Livewire swaps.
    document.addEventListener('livewire:navigate', sendDuration);

    // Initial load, then again after each SPA navigation.
    trackPage();
    document.addEventListener('livewire:navigated', trackPage);
}());

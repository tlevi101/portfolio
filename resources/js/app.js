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

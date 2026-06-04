// Theme toggle
(function () {
    const root = document.documentElement;
    const stored = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    let theme = stored ?? (prefersDark ? 'dark' : 'light');

    root.setAttribute('data-theme', theme);

    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.querySelector('[data-theme-toggle]');
        if (!btn) { return; }

        function updateBtn() {
            btn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
            btn.innerHTML = theme === 'dark'
                ? '<span aria-hidden="true">☀</span>'
                : '<span aria-hidden="true">◐</span>';
        }

        updateBtn();

        btn.addEventListener('click', function () {
            theme = theme === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            updateBtn();
        });
    });
}());

// Scroll-reveal
document.addEventListener('DOMContentLoaded', function () {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const items = document.querySelectorAll('.reveal');

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
});

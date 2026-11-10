/* Keep the sidebar footer inside the viewport as the institutional banner scrolls away. */
(() => {
    const root = document.documentElement;
    let frame;
    function update() {
        frame = null;
        const banner = document.querySelector('.ac-institution-bar');
        const offset = Math.max(0, Math.min(window.innerHeight, banner?.getBoundingClientRect().bottom ?? 0));
        root.style.setProperty('--sidebar-header-offset', `${offset}px`);
    }
    function schedule() {
        if (frame == null) frame = requestAnimationFrame(update);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', update, { once: true });
    else update();
    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule);
    window.addEventListener('pageshow', schedule);
    window.visualViewport?.addEventListener('resize', schedule);
    const banner = document.querySelector('.ac-institution-bar');
    if (banner && typeof ResizeObserver !== 'undefined') new ResizeObserver(schedule).observe(banner);
})();

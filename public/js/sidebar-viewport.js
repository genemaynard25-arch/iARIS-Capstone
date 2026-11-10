/* Measure banner height for menu spacing; scrolling is handled by native CSS stickiness. */
(() => {
    const root = document.documentElement;
    let frame;
    function update() {
        frame = null;
        const banner = document.querySelector('.ac-institution-bar');
        const height = `${banner?.getBoundingClientRect().height ?? 0}px`;
        if (root.style.getPropertyValue('--sidebar-header-height') !== height) {
            root.style.setProperty('--sidebar-header-height', height);
        }
    }
    function schedule() {
        if (frame == null) frame = requestAnimationFrame(update);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', update, { once: true });
    else update();
    window.addEventListener('resize', schedule);
    window.addEventListener('pageshow', schedule);
    window.visualViewport?.addEventListener('resize', schedule);
    const banner = document.querySelector('.ac-institution-bar');
    if (banner && typeof ResizeObserver !== 'undefined') new ResizeObserver(schedule).observe(banner);
})();

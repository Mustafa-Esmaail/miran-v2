// window.trackEvent is defined inline in layouts/app.blade.php so server-rendered
// <x-gtm-event> scripts can push before this deferred module loads.
window.addEventListener('gtm-event', (event) => {
    const detail = event.detail || {};
    if (detail.name && typeof window.trackEvent === 'function') {
        window.trackEvent(detail.name, detail.data || {});
    }
});

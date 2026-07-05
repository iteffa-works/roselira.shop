(function () {
    'use strict';

    var config = window.__FLOWAXY_ADMIN_ANALYTICS__;
    if (!config || typeof window.gtag !== 'function') {
        return;
    }

    if (config.gtmId) {
        return;
    }

    window.gtag('event', 'page_view', {
        page_title: document.title,
        page_location: window.location.href,
        page_path: window.location.pathname,
        content_group1: config.section || 'admin',
    });
})();

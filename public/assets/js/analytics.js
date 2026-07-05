(function () {
    'use strict';

    var loaded = false;

    function loadScript(src) {
        var script = document.createElement('script');
        script.async = true;
        script.src = src;
        document.head.appendChild(script);
    }

    function initMetaPixel(pixelId) {
        if (!pixelId || window.fbq) {
            return;
        }

        window.fbq = function () {
            window.fbq.callMethod
                ? window.fbq.callMethod.apply(window.fbq, arguments)
                : window.fbq.queue.push(arguments);
        };
        window.fbq.push = window.fbq;
        window.fbq.loaded = true;
        window.fbq.version = '2.0';
        window.fbq.queue = [];
        loadScript('https://connect.facebook.net/en_US/fbevents.js');
        window.fbq('init', pixelId);
        window.fbq('track', 'PageView');
    }

    function initGa4(measurementId) {
        if (!measurementId || window.gtag) {
            return;
        }

        window.dataLayer = window.dataLayer || [];
        window.gtag = function () {
            window.dataLayer.push(arguments);
        };
        window.gtag('js', new Date());
        loadScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurementId));
        window.gtag('config', measurementId);
    }

    function initGtm(containerId) {
        if (!containerId || document.querySelector('script[src*="googletagmanager.com/gtm.js"]')) {
            return;
        }

        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
        loadScript('https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(containerId));
    }

    function grantConsent() {
        if (typeof window.gtag === 'function') {
            window.gtag('consent', 'update', {
                analytics_storage: 'granted',
                ad_storage: 'granted',
                ad_user_data: 'granted',
                ad_personalization: 'granted',
            });
        }
    }

    window.FlowaxyAnalytics = {
        init: function (config) {
            if (loaded) {
                return;
            }

            loaded = true;
            config = config || window.__FLOWAXY__ || {};

            grantConsent();

            if (config.gtmId) {
                initGtm(config.gtmId);
            }
            if (config.ga4Id) {
                initGa4(config.ga4Id);
            }
            if (config.metaPixelId) {
                initMetaPixel(config.metaPixelId);
            }

            if (config.trackingProduct) {
                this.trackViewContent(config.trackingProduct);
            }
        },

        trackViewContent: function (product) {
            if (!product) {
                return;
            }

            if (window.fbq) {
                window.fbq('track', 'ViewContent', {
                    content_ids: [product.id],
                    content_name: product.name,
                    content_type: 'product',
                    value: product.price || 0,
                    currency: product.currency || 'UAH',
                });
            }

            if (window.gtag) {
                window.gtag('event', 'view_item', {
                    items: [{
                        item_id: product.id,
                        item_name: product.name,
                        price: product.price || 0,
                    }],
                });
            }
        },

        trackLead: function (payload) {
            payload = payload || {};

            if (window.fbq) {
                window.fbq('track', 'Lead', {
                    content_name: payload.product_name || '',
                    value: payload.value || 0,
                    currency: payload.currency || 'UAH',
                });
            }

            if (window.gtag) {
                window.gtag('event', 'generate_lead', {
                    item_id: payload.product_id || '',
                    value: payload.value || 0,
                    currency: payload.currency || 'UAH',
                });
            }
        },
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (window.FlowaxyConsent && window.FlowaxyConsent.hasAnalytics()) {
            window.FlowaxyAnalytics.init(window.__FLOWAXY__);
        }
    });
})();

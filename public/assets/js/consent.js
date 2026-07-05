(function () {
    'use strict';

    var CONSENT_KEY = 'flowaxy_cookie_consent';

    window.FlowaxyConsent = {
        get: function () {
            try {
                return localStorage.getItem(CONSENT_KEY);
            } catch (error) {
                return null;
            }
        },

        set: function (value) {
            try {
                localStorage.setItem(CONSENT_KEY, value);
            } catch (error) {
                /* ignore */
            }
        },

        hasAnalytics: function () {
            return this.get() === 'all';
        },
    };

    function initBanner() {
        var config = window.__FLOWAXY__;
        if (!config) {
            return;
        }

        var banner = document.querySelector('[data-cookie-banner]');
        if (!banner) {
            return;
        }

        var consent = window.FlowaxyConsent.get();
        if (consent) {
            if (consent === 'all' && window.FlowaxyAnalytics) {
                window.FlowaxyAnalytics.init(config);
            }
            return;
        }
        var textEl = banner.querySelector('[data-cookie-text]');
        var acceptBtn = banner.querySelector('[data-cookie-accept]');
        var rejectBtn = banner.querySelector('[data-cookie-reject]');

        if (textEl) {
            textEl.textContent = config.cookieText || '';
        }
        if (acceptBtn) {
            acceptBtn.textContent = config.cookieAccept || 'Accept';
        }
        if (rejectBtn) {
            rejectBtn.textContent = config.cookieReject || 'Reject';
        }

        banner.hidden = false;

        if (acceptBtn) {
            acceptBtn.addEventListener('click', function () {
                window.FlowaxyConsent.set('all');
                banner.hidden = true;
                if (window.FlowaxyAnalytics) {
                    window.FlowaxyAnalytics.init(config);
                }
            });
        }

        if (rejectBtn) {
            rejectBtn.addEventListener('click', function () {
                window.FlowaxyConsent.set('essential');
                banner.hidden = true;
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBanner);
    } else {
        initBanner();
    }
})();

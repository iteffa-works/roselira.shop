(function () {
    function resolveTheme(el) {
        var theme = el.getAttribute('data-recaptcha-theme') || 'auto';
        if (theme === 'auto') {
            return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        }

        return theme;
    }

    function renderWidget(el) {
        var widgetId = window.grecaptcha.render(el, {
            sitekey: el.getAttribute('data-recaptcha-sitekey'),
            theme: resolveTheme(el),
        });

        el.setAttribute('data-recaptcha-rendered', '1');
        el.setAttribute('data-recaptcha-widget-id', String(widgetId));
    }

    function resetWidgets() {
        if (!window.grecaptcha || typeof window.grecaptcha.reset !== 'function') {
            return;
        }

        document.querySelectorAll('[data-recaptcha-sitekey]').forEach(function (el) {
            var widgetId = el.getAttribute('data-recaptcha-widget-id');
            if (widgetId === null) {
                return;
            }

            try {
                window.grecaptcha.reset(parseInt(widgetId, 10));
            } catch (error) {
                /* widget may already be gone */
            }

            el.innerHTML = '';
            el.removeAttribute('data-recaptcha-rendered');
            el.removeAttribute('data-recaptcha-widget-id');
        });
    }

    function renderWidgets() {
        if (!window.grecaptcha || typeof window.grecaptcha.render !== 'function') {
            return;
        }

        document.querySelectorAll('[data-recaptcha-sitekey]').forEach(function (el) {
            if (el.getAttribute('data-recaptcha-rendered') === '1') {
                return;
            }

            renderWidget(el);
        });
    }

    function rerenderForThemeChange() {
        resetWidgets();
        renderWidgets();
    }

    window.flowaxyInitRecaptcha = renderWidgets;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderWidgets);
    } else {
        renderWidgets();
    }

    new MutationObserver(function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
            if (mutations[i].attributeName === 'data-theme') {
                rerenderForThemeChange();
                break;
            }
        }
    }).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme'],
    });
})();

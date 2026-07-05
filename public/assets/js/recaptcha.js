(function () {
    function resolveTheme(el) {
        var theme = el.getAttribute('data-recaptcha-theme') || 'auto';
        if (theme === 'auto') {
            return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        }

        return theme;
    }

    function renderWidgets() {
        if (!window.grecaptcha || typeof window.grecaptcha.render !== 'function') {
            return;
        }

        document.querySelectorAll('[data-recaptcha-sitekey]').forEach(function (el) {
            if (el.getAttribute('data-recaptcha-rendered') === '1') {
                return;
            }

            window.grecaptcha.render(el, {
                sitekey: el.getAttribute('data-recaptcha-sitekey'),
                theme: resolveTheme(el),
            });

            el.setAttribute('data-recaptcha-rendered', '1');
        });
    }

    window.flowaxyInitRecaptcha = renderWidgets;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderWidgets);
    } else {
        renderWidgets();
    }
})();

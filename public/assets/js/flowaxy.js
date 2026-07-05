(function () {
    var theme = window.FlowaxyTheme;
    if (!theme) {
        return;
    }

    var root = document.documentElement;
    var toggle = document.querySelector('.theme-toggle');
    var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

    function applyTheme(value) {
        theme.applyTheme(value);
        if (toggle) {
            toggle.setAttribute('aria-pressed', value === 'dark' ? 'true' : 'false');
        }
    }

    function setTheme(value) {
        try {
            localStorage.setItem(theme.storageKey, value);
        } catch (error) {
            // Ignore storage failures (private mode).
        }
        applyTheme(value);
    }

    applyTheme(theme.resolveTheme());

    if (mediaQuery.addEventListener) {
        mediaQuery.addEventListener('change', function (event) {
            if (!theme.getSavedTheme()) {
                applyTheme(event.matches ? 'dark' : 'light');
            }
        });
    } else if (mediaQuery.addListener) {
        mediaQuery.addListener(function (event) {
            if (!theme.getSavedTheme()) {
                applyTheme(event.matches ? 'dark' : 'light');
            }
        });
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            var current = root.getAttribute('data-theme') || theme.getSystemTheme();
            setTheme(current === 'dark' ? 'light' : 'dark');
        });
    }
})();

window.FlowaxyTheme = (function () {
    var storageKey = 'flowaxy_theme';

    function getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function getSavedTheme() {
        try {
            return localStorage.getItem(storageKey);
        } catch (error) {
            return null;
        }
    }

    function resolveTheme() {
        return getSavedTheme() || getSystemTheme();
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
    }

    return {
        storageKey: storageKey,
        getSystemTheme: getSystemTheme,
        getSavedTheme: getSavedTheme,
        resolveTheme: resolveTheme,
        applyTheme: applyTheme,
    };
})();

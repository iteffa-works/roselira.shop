<!DOCTYPE html>
<html lang="<?= e($locale ?? currentLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <script src="<?= asset('assets/js/theme-init.js') ?>"></script>
    <script>window.FlowaxyTheme.applyTheme(window.FlowaxyTheme.resolveTheme());</script>
    <title><?= e($title ?? 'Roselira') ?></title>
    <meta name="description" content="<?= e($description ?? '') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/brand/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/img/brand/favicon.svg') ?>">
    <?php if (!empty($ogImage)): ?>
    <meta property="og:title" content="<?= e($title ?? '') ?>">
    <meta property="og:description" content="<?= e($description ?? '') ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:type" content="website">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= asset('assets/css/flowaxy.css') ?>">
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a href="/" class="site-logo">
                <img
                    class="site-logo__img site-logo__img--on-light"
                    src="<?= asset('assets/img/brand/logo-dark.svg') ?>"
                    alt="Roselira"
                    width="160"
                    height="40"
                >
                <img
                    class="site-logo__img site-logo__img--on-dark"
                    src="<?= asset('assets/img/brand/logo-light.svg') ?>"
                    alt="Roselira"
                    width="160"
                    height="40"
                >
            </a>
            <div class="header-controls">
                <nav class="lang-switcher" aria-label="Language">
                    <?php foreach ($publicLocales as $lang): ?>
                    <a
                        href="<?= e(langUrl($lang)) ?>"
                        class="lang-switcher__btn<?= ($locale ?? currentLocale()) === $lang ? ' is-active' : '' ?>"
                        hreflang="<?= e($lang) ?>"
                    ><?= $lang === 'uk' ? 'UA' : strtoupper($lang) ?></a>
                    <?php endforeach; ?>
                </nav>
                <button type="button" class="theme-toggle" aria-label="<?= e(t('theme_toggle')) ?>" aria-pressed="false">
                    <svg class="theme-toggle__icon theme-toggle__icon--light" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                    <svg class="theme-toggle__icon theme-toggle__icon--dark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main class="site-main">
        <?php require __DIR__ . '/' . ($content ?? 'home') . '.php'; ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= e(t('footer')) ?></p>
        </div>
    </footer>
    <script src="<?= asset('assets/js/flowaxy.js') ?>" defer></script>
    <?php if (!empty($pageScript)): ?>
    <script src="<?= asset('assets/js/' . $pageScript . '.js') ?>" defer></script>
    <?php endif; ?>
</body>
</html>

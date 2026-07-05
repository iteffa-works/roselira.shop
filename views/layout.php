<!DOCTYPE html>
<html lang="<?= e($locale ?? currentLocale()) ?>">
<head>
    <?php
    $siteConfig = app_config();
    $canonicalPath = $canonicalPath ?? (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $canonicalUrl = absolute_url($canonicalPath);
    $ogImageUrl = !empty($ogImage) ? absolute_url((string) $ogImage) : '';
    $hasTracking = ($siteConfig['meta_pixel_id'] ?? '') !== ''
        || ($siteConfig['ga4_measurement_id'] ?? '') !== ''
        || ($siteConfig['gtm_container_id'] ?? '') !== '';
    $gtmId = (string) ($siteConfig['gtm_container_id'] ?? '');
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <script src="<?= asset('assets/js/theme-init.js') ?>"></script>
    <script>window.FlowaxyTheme.applyTheme(window.FlowaxyTheme.resolveTheme());</script>
    <title><?= e($title ?? 'Roselira') ?></title>
    <meta name="description" content="<?= e($description ?? '') ?>">
    <meta property="og:title" content="<?= e($title ?? '') ?>">
    <meta property="og:description" content="<?= e($description ?? '') ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:locale" content="<?= ($locale ?? currentLocale()) === 'ru' ? 'ru_RU' : 'uk_UA' ?>">
    <?php if ($ogImageUrl !== ''): ?>
    <meta property="og:image" content="<?= e($ogImageUrl) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title ?? '') ?>">
    <meta name="twitter:description" content="<?= e($description ?? '') ?>">
    <?php if ($ogImageUrl !== ''): ?>
    <meta name="twitter:image" content="<?= e($ogImageUrl) ?>">
    <?php endif; ?>
    <?php if (!empty($jsonLd)): ?>
    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif; ?>
    <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/brand/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/img/brand/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/flowaxy.css') ?>">
    <?php if ($gtmId !== ''): ?>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('consent', 'default', {
        analytics_storage: 'denied',
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        wait_for_update: 500
    });
    </script>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($gtmId) ?>');</script>
    <?php endif; ?>
    <?php if ($hasTracking): ?>
    <script>
    window.__FLOWAXY__ = <?= json_encode([
        'metaPixelId' => $siteConfig['meta_pixel_id'] ?? '',
        'ga4Id' => $siteConfig['ga4_measurement_id'] ?? '',
        'gtmId' => $siteConfig['gtm_container_id'] ?? '',
        'trackingProduct' => $trackingProduct ?? null,
        'cookieAccept' => t('cookie_accept'),
        'cookieReject' => t('cookie_reject'),
        'cookieText' => t('cookie_banner_text'),
    ], JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <?php endif; ?>
</head>
<body>
    <?php if ($gtmId !== ''): ?>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($gtmId) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <?php endif; ?>
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

    <?php
    $contactEmail = (string) ($siteConfig['contact_email'] ?? '');
    $contactTelegram = (string) ($siteConfig['contact_telegram'] ?? '');
    ?>
    <footer class="site-footer">
        <div class="container site-footer__bar">
            <div class="site-footer__start">
                <nav class="site-footer__nav" aria-label="Legal">
                    <a href="/privacy"><?= e(t('footer_privacy')) ?></a>
                    <a href="/terms"><?= e(t('footer_terms')) ?></a>
                    <a href="/delivery"><?= e(t('footer_delivery')) ?></a>
                </nav>
                <?php if ($contactEmail !== '' || $contactTelegram !== ''): ?>
                <span class="site-footer__contact">
                    <?= e(t('footer_contact')) ?>:
                    <?php if ($contactEmail !== ''): ?>
                    <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
                    <?php endif; ?>
                    <?php if ($contactTelegram !== ''): ?>
                    <?php if ($contactEmail !== ''): ?><span class="site-footer__contact-sep">·</span><?php endif; ?>
                    <a href="https://t.me/<?= e(ltrim($contactTelegram, '@')) ?>" target="_blank" rel="noopener"><?= e($contactTelegram) ?></a>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="site-footer__end">
                <span class="site-footer__copy">© <?= date('Y') ?> Roselira · roselira.shop</span>
                <span class="site-footer__credit">
                    <?= e(t('footer_credit')) ?> <a href="https://flowaxy.com" target="_blank" rel="noopener">Flowaxy Digital Studio</a>
                </span>
            </div>
        </div>
    </footer>

    <?php if ($hasTracking): ?>
    <div class="cookie-banner" data-cookie-banner hidden>
        <p class="cookie-banner__text" data-cookie-text></p>
        <div class="cookie-banner__actions">
            <button type="button" class="cookie-banner__btn cookie-banner__btn--accept" data-cookie-accept></button>
            <button type="button" class="cookie-banner__btn cookie-banner__btn--reject" data-cookie-reject></button>
        </div>
    </div>
    <?php endif; ?>

    <script src="<?= asset('assets/js/flowaxy.js') ?>" defer></script>
    <script src="<?= asset('assets/js/visitor-track.js') ?>" defer></script>
    <?php if ($hasTracking): ?>
    <script src="<?= asset('assets/js/consent.js') ?>" defer></script>
    <script src="<?= asset('assets/js/analytics.js') ?>" defer></script>
    <?php endif; ?>
    <?php if (!empty($pageScript)): ?>
    <script src="<?= asset('assets/js/' . $pageScript . '.js') ?>" defer></script>
    <?php endif; ?>
    <?php if (!empty($loadRecaptcha)): ?>
    <script src="<?= asset('assets/js/recaptcha.js') ?>"></script>
    <script src="https://www.google.com/recaptcha/api.js?onload=flowaxyInitRecaptcha&render=explicit" async defer></script>
    <?php endif; ?>
</body>
</html>

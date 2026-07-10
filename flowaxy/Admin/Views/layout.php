<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Admin') ?> — Roselira</title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/brand/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/img/brand/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
    <?php require __DIR__ . '/partials/tracking.php'; ?>
    <?php if (($template ?? '') === 'login' && recaptcha_enabled()): ?>
    <script src="<?= asset('assets/js/recaptcha.js') ?>"></script>
    <script src="https://www.google.com/recaptcha/api.js?onload=flowaxyInitRecaptcha&render=explicit" async defer></script>
    <?php endif; ?>
</head>
<body class="admin<?= isset($template) && ($template === 'login' || $template === 'install') ? ' admin--auth' : '' ?>"<?= ($template ?? '') !== 'login' && ($template ?? '') !== 'install' ? ' data-admin-shell' : '' ?>>
<?php
$adminGtmId = (string) (app_config()['gtm_container_id'] ?? '');
if ($adminGtmId !== ''): ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($adminGtmId) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>
<?php if (!empty($flash)): ?>
<div class="admin-flash admin-flash--<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div>
<?php endif; ?>

<?php if (($template ?? '') === 'login' || ($template ?? '') === 'install'): ?>
<main class="admin-auth">
    <div class="admin-auth__backdrop" aria-hidden="true"></div>
    <div class="admin-auth__panel">
    <?php if (($template ?? '') === 'login'): ?>
        <?php require __DIR__ . '/login_form.php'; ?>
    <?php else: ?>
        <div class="admin-auth-card">
            <header class="admin-auth-card__head">
                <h1 class="admin-auth-card__title">Налаштування адмінки</h1>
                <p class="admin-auth-card__lead">Створіть обліковий запис адміністратора</p>
            </header>
            <?php if (!empty($error)): ?><div class="admin-auth-card__alert" role="alert"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="admin-auth-form">
                <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">
                <label class="admin-auth-form__field">
                    <span class="admin-auth-form__label">Логін</span>
                    <input type="text" name="username" value="admin" required>
                </label>
                <label class="admin-auth-form__field">
                    <span class="admin-auth-form__label">Пароль</span>
                    <input type="password" name="password" required minlength="6">
                </label>
                <label class="admin-auth-form__field">
                    <span class="admin-auth-form__label">Підтвердження</span>
                    <input type="password" name="password_confirm" required minlength="6">
                </label>
                <button type="submit" class="admin-btn admin-btn--block admin-auth-form__submit">Створити</button>
            </form>
        </div>
    <?php endif; ?>
    </div>
</main>
<?php else: ?>
<?php
$currentPage = $page ?? '';
$nav = [
    'dashboard' => ['label' => 'Dashboard', 'url' => admin_url()],
    'orders' => ['label' => 'Замовлення', 'url' => admin_url('orders')],
    'catalog' => ['label' => 'Каталог', 'url' => admin_url('catalog')],
    'categories' => ['label' => 'Категорії', 'url' => admin_url('categories')],
    'locales' => ['label' => 'Тексти UI', 'url' => admin_url('locales')],
    'pages' => ['label' => 'Сторінки', 'url' => admin_url('pages')],
    'rates' => ['label' => 'Курси', 'url' => admin_url('rates')],
    'notifications' => ['label' => 'Сповіщення', 'url' => admin_url('notifications')],
    'database' => ['label' => 'База даних', 'url' => admin_url('database')],
    'security' => ['label' => 'Безпека', 'url' => admin_url('security')],
    'system' => ['label' => 'Система', 'url' => admin_url('system')],
];
?>
<div class="admin-shell">
    <div class="admin-overlay" data-sidebar-overlay></div>
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Навігація">
        <div class="admin-sidebar__head">
            <a href="<?= admin_url() ?>" class="admin-logo">
                <img src="<?= asset('assets/img/brand/logo-light.svg') ?>" alt="Roselira" width="140" height="35">
            </a>
            <button type="button" class="admin-sidebar__close" data-sidebar-close aria-label="Закрити меню">×</button>
        </div>
        <nav class="admin-nav">
            <?php foreach ($nav as $key => $item): ?>
            <a href="<?= e($item['url']) ?>" class="<?= $currentPage === $key ? 'is-active' : '' ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar__footer">
            <a href="/" target="_blank" class="admin-sidebar__link">Відкрити сайт ↗</a>
            <a href="<?= admin_url('logout') ?>" class="admin-sidebar__link admin-sidebar__link--muted">Вийти</a>
        </div>
    </aside>
    <div class="admin-content">
        <header class="admin-topbar">
            <button type="button" class="admin-menu-btn" data-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Відкрити меню">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <a href="<?= admin_url() ?>" class="admin-topbar__logo">
                <img src="<?= asset('assets/img/brand/logo-light.svg') ?>" alt="Roselira" width="120" height="30">
            </a>
        </header>
        <main class="admin-main">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>
<script src="<?= asset('assets/js/admin.js') ?>" defer></script>
<?php endif; ?>
<?php if (!empty(app_config()['gtm_container_id']) || !empty(app_config()['ga4_measurement_id'])): ?>
<script src="<?= asset('assets/js/admin-analytics.js') ?>" defer></script>
<?php endif; ?>
</body>
</html>

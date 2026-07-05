<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Admin') ?> — Roselira</title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/brand/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/img/brand/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
</head>
<body class="admin<?= isset($template) && $template === 'login' ? ' admin--auth' : '' ?>"<?= ($template ?? '') !== 'login' && ($template ?? '') !== 'install' ? ' data-admin-shell' : '' ?>>
<?php if (!empty($flash)): ?>
<div class="admin-flash admin-flash--<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div>
<?php endif; ?>

<?php if (($template ?? '') === 'login' || ($template ?? '') === 'install'): ?>
<main class="admin-auth">
    <?php if (($template ?? '') === 'login'): ?>
        <?php require __DIR__ . '/login_form.php'; ?>
    <?php else: ?>
        <h1>Налаштування адмінки</h1>
        <?php if (!empty($error)): ?><p class="admin-error"><?= e($error) ?></p><?php endif; ?>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">
            <label>Логін <input type="text" name="username" value="admin" required></label>
            <label>Пароль <input type="password" name="password" required minlength="6"></label>
            <label>Підтвердження <input type="password" name="password_confirm" required minlength="6"></label>
            <button type="submit">Створити</button>
        </form>
    <?php endif; ?>
</main>
<?php else: ?>
<?php
$currentPage = $page ?? '';
$nav = [
    'dashboard' => ['label' => 'Dashboard', 'url' => admin_url()],
    'orders' => ['label' => 'Замовлення', 'url' => admin_url('orders')],
    'catalog' => ['label' => 'Каталог', 'url' => admin_url('catalog')],
    'locales' => ['label' => 'Тексти UI', 'url' => admin_url('locales')],
    'rates' => ['label' => 'Курси', 'url' => admin_url('rates')],
    'notifications' => ['label' => 'Сповіщення', 'url' => admin_url('notifications')],
    'database' => ['label' => 'База даних', 'url' => admin_url('database')],
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
</body>
</html>

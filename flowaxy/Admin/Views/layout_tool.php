<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Admin') ?> — Roselira</title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/brand/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
    <?php require __DIR__ . '/partials/tracking.php'; ?>
</head>
<body class="admin admin--tool">
<?php if (!empty($flash)): ?>
<div class="admin-flash admin-flash--<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div>
<?php endif; ?>
<header class="admin-tool-bar admin-tool-bar--compact">
    <h1 class="admin-tool-bar__title"><?= e($title ?? 'Admin') ?></h1>
    <a href="<?= admin_url() ?>" class="admin-btn admin-btn--ghost admin-btn--sm">← Dashboard</a>
</header>
<main class="admin-tool-main admin-tool-main--compact">
    <?= $content ?? '' ?>
</main>
<?php if (!empty(app_config()['gtm_container_id']) || !empty(app_config()['ga4_measurement_id'])): ?>
<script src="<?= asset('assets/js/admin-analytics.js') ?>" defer></script>
<?php endif; ?>
</body>
</html>

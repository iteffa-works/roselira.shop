<?php
$statusLabels = ['new' => 'Нове', 'done' => 'Виконано', 'cancelled' => 'Скасовано'];
$analytics = $analytics ?? [];
$analyticsSource = $analyticsSource ?? 'local';
$googleTabAvailable = $googleTabAvailable ?? false;
$googleReport = $googleReport ?? null;
$days = (int) ($analytics['days'] ?? 7);

$analyticsUrl = static function (string $source) use ($days): string {
    return admin_url('?source=' . rawurlencode($source) . '&days=' . (int) $days);
};

$formatDuration = static function (int $seconds): string {
    if ($seconds < 60) {
        return $seconds . ' с';
    }
    $minutes = intdiv($seconds, 60);
    $rest = $seconds % 60;

    return $minutes . ' хв ' . $rest . ' с';
};

$formatDt = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $ts = strtotime($value);

    return $ts === false ? $value : date('d.m.Y H:i', $ts);
};
?>

<div class="admin-page-header">
    <div>
        <h1>Dashboard</h1>
        <p class="admin-muted">Огляд магазину Roselira та поведінка відвідувачів</p>
    </div>
</div>

<div class="admin-stats">
    <div class="admin-stat admin-stat--accent">
        <span class="admin-stat__value"><?= (int) $newCount ?></span>
        <span class="admin-stat__label">Нові замовлення</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__value"><?= (int) $ordersCount ?></span>
        <span class="admin-stat__label">Всього замовлень</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__value"><?= (int) $activeCount ?></span>
        <span class="admin-stat__label">Активних товарів</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__value"><?= e(number_format($rates['EUR'], 2)) ?></span>
        <span class="admin-stat__label">EUR (<?= e($rates['date'] ?: '—') ?>)</span>
    </div>
</div>

<section class="admin-card admin-analytics">
    <div class="admin-card__head admin-analytics__head">
        <div>
            <h2 class="admin-card__title">Аналітика відвідувачів</h2>
            <p class="admin-muted">
                <?php if ($analyticsSource === 'google'): ?>
                Google Analytics · <?= $days === 1 ? 'realtime GA4' : 'дані з GA4' ?>
                <?php else: ?>
                Локальний трекінг · кліки, scroll, сесії
                <?php endif; ?>
            </p>
        </div>
        <div class="admin-analytics__toolbar">
            <?php if ($googleTabAvailable): ?>
            <div class="admin-analytics__source" role="tablist" aria-label="Джерело аналітики">
                <a href="<?= e($analyticsUrl('local')) ?>" class="admin-analytics__source-btn<?= $analyticsSource === 'local' ? ' is-active' : '' ?>" role="tab">Локальна</a>
                <a href="<?= e($analyticsUrl('google')) ?>" class="admin-analytics__source-btn<?= $analyticsSource === 'google' ? ' is-active' : '' ?>" role="tab">Google</a>
            </div>
            <?php endif; ?>
            <div class="admin-analytics__period" role="tablist" aria-label="Період">
                <?php foreach ([7 => '7 днів', 30 => '30 днів', 1 => 'Сьогодні'] as $period => $label): ?>
                <a
                    href="<?= e(admin_url('?source=' . rawurlencode($analyticsSource) . '&days=' . $period)) ?>"
                    class="admin-analytics__period-btn<?= $days === $period ? ' is-active' : '' ?>"
                ><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($analyticsSource === 'google' && is_array($googleReport)): ?>
        <?php require __DIR__ . '/partials/dashboard_analytics_google.php'; ?>
    <?php else: ?>
        <?php require __DIR__ . '/partials/dashboard_analytics_local.php'; ?>
    <?php endif; ?>
</section>

<div class="admin-grid">
    <section class="admin-card">
        <h2 class="admin-card__title">Швидкі дії</h2>
        <div class="admin-quick-links">
            <a href="<?= admin_url('orders') ?>" class="admin-quick-link">Замовлення<?php if ($newCount > 0): ?> <span class="admin-badge admin-badge--new"><?= (int) $newCount ?></span><?php endif; ?></a>
            <a href="<?= admin_url('catalog') ?>" class="admin-quick-link">Каталог</a>
            <a href="<?= admin_url('locales') ?>" class="admin-quick-link">Тексти UI</a>
            <a href="<?= admin_url('rates') ?>" class="admin-quick-link">Оновити курси НБУ</a>
            <a href="<?= admin_url('notifications') ?>" class="admin-quick-link">Сповіщення</a>
            <a href="<?= admin_url('database') ?>" class="admin-quick-link">База даних</a>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <h2 class="admin-card__title">Останні замовлення</h2>
            <a href="<?= admin_url('orders') ?>" class="admin-link">Усі →</a>
        </div>
        <?php if (($recentOrders ?? []) === []): ?>
        <p class="admin-empty">Замовлень ще немає.</p>
        <?php else: ?>
        <div class="admin-card-list admin-card-list--inset">
            <?php foreach ($recentOrders as $order): ?>
            <?php $status = (string) ($order['status'] ?? 'new'); ?>
            <article class="admin-item-card admin-item-card--compact">
                <header class="admin-item-card__head">
                    <strong class="admin-item-card__title"><?= e((string) ($order['product_name'] ?? $order['product_slug'] ?? '')) ?></strong>
                    <span class="admin-badge admin-badge--<?= e($status) ?>"><?= e($statusLabels[$status] ?? $status) ?></span>
                </header>
                <dl class="admin-item-card__meta admin-item-card__meta--inline">
                    <div><dt>Дата</dt><dd><?= e(substr((string) ($order['created_at'] ?? ''), 0, 10)) ?></dd></div>
                    <div><dt>Клієнт</dt><dd><?= e((string) ($order['customer_name'] ?? '')) ?></dd></div>
                </dl>
            </article>
            <?php endforeach; ?>
        </div>
        <div class="admin-table-wrap admin-table-wrap--desktop">
        <table class="admin-table admin-table--compact">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Товар</th>
                    <th>Клієнт</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td><?= e(substr((string) ($order['created_at'] ?? ''), 0, 10)) ?></td>
                    <td><?= e((string) ($order['product_name'] ?? $order['product_slug'] ?? '')) ?></td>
                    <td><?= e((string) ($order['customer_name'] ?? '')) ?></td>
                    <td><span class="admin-badge admin-badge--<?= e((string) ($order['status'] ?? 'new')) ?>"><?= e($statusLabels[$order['status'] ?? 'new'] ?? $order['status'] ?? 'new') ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php if ($analyticsSource === 'google' && is_array($googleReport) && ($googleReport['mode'] ?? '') === 'api'): ?>
<script src="<?= asset('assets/js/admin-dashboard.js') ?>" defer></script>
<?php endif; ?>

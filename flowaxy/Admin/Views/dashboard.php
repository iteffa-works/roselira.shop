<?php
$statusLabels = ['new' => 'Нове', 'done' => 'Виконано', 'cancelled' => 'Скасовано'];
?>

<div class="admin-page-header">
    <div>
        <h1>Dashboard</h1>
        <p class="admin-muted">Огляд магазину Roselira</p>
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

<?php
$statusLabels = ['new' => 'Нове', 'done' => 'Виконано', 'cancelled' => 'Скасовано'];
$filterLabels = ['new' => 'Нові', 'done' => 'Виконані', 'cancelled' => 'Скасовані'];
?>

<div class="admin-page-header">
    <div>
        <h1>Замовлення</h1>
        <p class="admin-muted"><?= count($orders) ?> записів<?= $filter !== '' ? ' · фільтр: ' . e($filterLabels[$filter] ?? $filter) : '' ?></p>
    </div>
    <a href="<?= admin_url('database') ?>" class="admin-btn admin-btn--ghost">Очистити БД</a>
</div>

<div class="admin-filters">
    <a href="<?= admin_url('orders') ?>" class="<?= $filter === '' ? 'is-active' : '' ?>">Всі</a>
    <?php foreach ($statuses as $status): ?>
    <a href="<?= admin_url('orders', ['status' => $status]) ?>" class="<?= $filter === $status ? 'is-active' : '' ?>"><?= e($filterLabels[$status] ?? $status) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($orders === []): ?>
<p class="admin-empty">Замовлень немає.</p>
<?php else: ?>

<div class="admin-card-list">
    <?php foreach ($orders as $order): ?>
    <?php
        $orderId = (string) ($order['id'] ?? '');
        $status = (string) ($order['status'] ?? 'new');
        $productName = (string) ($order['product_name'] ?? $order['product_slug'] ?? '');
        $variantName = (string) ($order['variant_name'] ?? $order['variant_id'] ?? '');
        $priceText = isset($order['price']) ? formatPrice((float) $order['price'], (string) ($order['price_currency'] ?? 'UAH')) : '—';
    ?>
    <article class="admin-item-card">
        <header class="admin-item-card__head">
            <strong class="admin-item-card__title"><?= e($productName) ?></strong>
            <span class="admin-badge admin-badge--<?= e($status) ?>"><?= e($statusLabels[$status] ?? $status) ?></span>
        </header>
        <dl class="admin-item-card__meta">
            <div><dt>Дата</dt><dd><?= e(substr((string) ($order['created_at'] ?? ''), 0, 16)) ?></dd></div>
            <div><dt>Клієнт</dt><dd><?= e((string) ($order['customer_name'] ?? '')) ?><br><span class="admin-muted"><?= e((string) ($order['customer_phone'] ?? '')) ?></span></dd></div>
            <?php if ($variantName !== ''): ?>
            <div><dt>Відтінок</dt><dd><?= e($variantName) ?></dd></div>
            <?php endif; ?>
            <div><dt>Ціна</dt><dd><?= e($priceText) ?></dd></div>
            <div><dt>ID</dt><dd><code class="admin-code"><?= e($orderId) ?></code></dd></div>
        </dl>
        <?php if (!empty($order['comment'])): ?>
        <p class="admin-item-card__note"><em><?= e((string) $order['comment']) ?></em></p>
        <?php endif; ?>
        <footer class="admin-item-card__actions">
            <form method="post" class="admin-inline-form admin-inline-form--grow">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="order_id" value="<?= e($orderId) ?>">
                <select name="status">
                    <?php foreach ($statuses as $statusOption): ?>
                    <option value="<?= e($statusOption) ?>" <?= $status === $statusOption ? 'selected' : '' ?>><?= e($statusLabels[$statusOption] ?? $statusOption) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="admin-btn admin-btn--sm">OK</button>
            </form>
            <form method="post" action="<?= admin_url('orders/delete', $filter !== '' ? ['status' => $filter] : []) ?>" class="admin-inline-form" onsubmit="return confirm('Видалити це замовлення?')">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="order_id" value="<?= e($orderId) ?>">
                <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger" title="Видалити">×</button>
            </form>
        </footer>
    </article>
    <?php endforeach; ?>
</div>

<div class="admin-table-wrap admin-table-wrap--desktop">
<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Дата</th>
            <th>Товар</th>
            <th>Відтінок</th>
            <th>Ціна</th>
            <th>Клієнт</th>
            <th>Статус</th>
            <th>Дії</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
        <tr>
            <td><code class="admin-code"><?= e((string) ($order['id'] ?? '')) ?></code></td>
            <td><?= e(substr((string) ($order['created_at'] ?? ''), 0, 16)) ?></td>
            <td><?= e((string) ($order['product_name'] ?? $order['product_slug'] ?? '')) ?></td>
            <td><?= e((string) ($order['variant_name'] ?? $order['variant_id'] ?? '')) ?></td>
            <td><?= e(isset($order['price']) ? formatPrice((float) $order['price'], (string) ($order['price_currency'] ?? 'UAH')) : '—') ?></td>
            <td><?= e((string) ($order['customer_name'] ?? '')) ?><br><small class="admin-muted"><?= e((string) ($order['customer_phone'] ?? '')) ?></small></td>
            <td><span class="admin-badge admin-badge--<?= e((string) ($order['status'] ?? 'new')) ?>"><?= e($statusLabels[$order['status'] ?? 'new'] ?? $order['status'] ?? 'new') ?></span></td>
            <td class="admin-actions-cell">
                <form method="post" class="admin-inline-form">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="order_id" value="<?= e((string) ($order['id'] ?? '')) ?>">
                    <select name="status">
                        <?php foreach ($statuses as $status): ?>
                        <option value="<?= e($status) ?>" <?= ($order['status'] ?? '') === $status ? 'selected' : '' ?>><?= e($statusLabels[$status] ?? $status) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="admin-btn admin-btn--sm">OK</button>
                </form>
                <form method="post" action="<?= admin_url('orders/delete', $filter !== '' ? ['status' => $filter] : []) ?>" class="admin-inline-form" onsubmit="return confirm('Видалити це замовлення?')">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="order_id" value="<?= e((string) ($order['id'] ?? '')) ?>">
                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger" title="Видалити">×</button>
                </form>
            </td>
        </tr>
        <?php if (!empty($order['comment'])): ?>
        <tr class="admin-table__sub"><td colspan="8"><em><?= e((string) $order['comment']) ?></em></td></tr>
        <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

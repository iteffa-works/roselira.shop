<?php
$formatDt = static function (string $iso): string {
    $ts = strtotime($iso);

    return $ts !== false ? date('d.m.Y H:i:s', $ts) : $iso;
};
?>

<div class="admin-page-header">
    <div>
        <h1>Безпека</h1>
        <p class="admin-muted">Логи замовлень і входу · фільтр IP, браузера · скидання лімітів</p>
    </div>
</div>

<div class="admin-stats admin-security__stats">
    <div class="admin-stat">
        <span class="admin-stat__value"><?= (int) ($stats['total'] ?? 0) ?></span>
        <span class="admin-stat__label">Подій всього</span>
    </div>
    <div class="admin-stat admin-stat--ok">
        <span class="admin-stat__value"><?= (int) ($stats['ok'] ?? 0) ?></span>
        <span class="admin-stat__label">Клієнти (OK)</span>
    </div>
    <div class="admin-stat admin-stat--warn">
        <span class="admin-stat__value"><?= (int) ($stats['suspect'] ?? 0) ?></span>
        <span class="admin-stat__label">Підозріло</span>
    </div>
    <div class="admin-stat admin-stat--error">
        <span class="admin-stat__value"><?= (int) ($stats['fraud'] ?? 0) ?></span>
        <span class="admin-stat__label">Мошенники</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__value"><?= (int) ($stats['rate_limited'] ?? 0) ?></span>
        <span class="admin-stat__label">Ліміт / 24 год</span>
    </div>
</div>

<div class="admin-grid admin-grid--split">
    <section class="admin-card">
        <h2 class="admin-card__title">Скинути ліміти</h2>
        <p class="admin-muted admin-card__desc">Якщо клієнт бачить «Забагато спроб» — скиньте ліміт для його IP.</p>

        <form method="post" action="<?= admin_url('security/action') ?>" class="admin-form__row admin-security__clear-ip">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="clear_rate_ip">
            <?php foreach ($filters as $key => $value): ?>
            <input type="hidden" name="filter_<?= e($key) ?>" value="<?= e($value) ?>">
            <?php endforeach; ?>
            <label class="admin-field">
                <span class="admin-field__label">IP адреса</span>
                <input type="text" name="ip" value="<?= e($filters['ip']) ?>" placeholder="192.168.1.1">
            </label>
            <button type="submit" class="admin-btn admin-btn--telegram">Скинути IP</button>
        </form>

        <form method="post" action="<?= admin_url('security/action') ?>" class="admin-actions" onsubmit="return confirm('Скинути всі ліміти замовлень і входу?')">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="clear_rate_limits">
            <?php foreach ($filters as $key => $value): ?>
            <input type="hidden" name="filter_<?= e($key) ?>" value="<?= e($value) ?>">
            <?php endforeach; ?>
            <button type="submit" class="admin-btn admin-btn--outline admin-btn--block">Скинути всі ліміти</button>
        </form>
    </section>

    <section class="admin-card admin-card--danger">
        <h2 class="admin-card__title">Очистити логи</h2>
        <form method="post" action="<?= admin_url('security/action') ?>" class="admin-actions">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="clear_logs_30">
            <?php foreach ($filters as $key => $value): ?>
            <input type="hidden" name="filter_<?= e($key) ?>" value="<?= e($value) ?>">
            <?php endforeach; ?>
            <button type="submit" class="admin-btn admin-btn--block">Старіші 30 днів</button>
        </form>
        <form method="post" action="<?= admin_url('security/action') ?>" class="admin-actions" onsubmit="return confirm('Видалити ВСІ логи безпеки?')">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="clear_logs_all">
            <?php foreach ($filters as $key => $value): ?>
            <input type="hidden" name="filter_<?= e($key) ?>" value="<?= e($value) ?>">
            <?php endforeach; ?>
            <button type="submit" class="admin-btn admin-btn--danger admin-btn--block">Очистити все</button>
        </form>
    </section>
</div>

<section class="admin-card">
    <h2 class="admin-card__title">Журнал подій</h2>

    <form method="get" action="<?= admin_url('security') ?>" class="admin-security__filters">
        <label>
            <span>IP</span>
            <input type="text" name="ip" value="<?= e($filters['ip']) ?>" placeholder="Частина IP">
        </label>
        <label>
            <span>Тип</span>
            <select name="event_type">
                <option value="">Усі</option>
                <?php foreach ($eventLabels as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $filters['event_type'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Оцінка</span>
            <select name="verdict">
                <option value="">Усі</option>
                <?php foreach ($verdictLabels as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $filters['verdict'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="admin-security__filters-wide">
            <span>Браузер / шлях</span>
            <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Chrome, /order, product_slug…">
        </label>
        <button type="submit" class="admin-btn">Фільтр</button>
        <a href="<?= admin_url('security') ?>" class="admin-btn admin-btn--ghost">Скинути</a>
    </form>

    <?php if ($events === []): ?>
    <p class="admin-empty">Подій не знайдено.</p>
    <?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table admin-table--compact admin-security__table">
            <thead>
                <tr>
                    <th>Час</th>
                    <th>Оцінка</th>
                    <th>Подія</th>
                    <th>IP</th>
                    <th>Браузер</th>
                    <th>Деталі</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $event):
                $browser = \Flowaxy\Support\RequestContext::browserLabel((string) ($event['user_agent'] ?? ''));
                $meta = $event['meta'] ?? [];
                $details = [];
                if (!empty($meta['message'])) {
                    $details[] = (string) $meta['message'];
                }
                if (!empty($meta['product_slug'])) {
                    $details[] = 'товар: ' . (string) $meta['product_slug'];
                }
                if (!empty($meta['phone'])) {
                    $details[] = 'тел: ' . (string) $meta['phone'];
                }
                if (!empty($meta['username'])) {
                    $details[] = 'логін: ' . (string) $meta['username'];
                }
            ?>
                <tr>
                    <td><?= e($formatDt((string) ($event['created_at'] ?? ''))) ?></td>
                    <td><span class="admin-security__verdict admin-security__verdict--<?= e((string) ($event['verdict'] ?? '')) ?>"><?= e($verdictLabels[$event['verdict'] ?? ''] ?? $event['verdict'] ?? '') ?></span></td>
                    <td><?= e($eventLabels[$event['event_type'] ?? ''] ?? $event['event_type'] ?? '') ?></td>
                    <td><code><?= e((string) ($event['ip'] ?? '')) ?></code></td>
                    <td title="<?= e((string) ($event['user_agent'] ?? '')) ?>"><?= e($browser) ?></td>
                    <td class="admin-muted"><?= e(implode(' · ', $details)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

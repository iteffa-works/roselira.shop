<?php
$formatDt = static function (string $iso): string {
    $ts = strtotime($iso);

    return $ts !== false ? date('d.m.Y H:i:s', $ts) : $iso;
};

$total = (int) ($stats['total'] ?? 0);
$ok = (int) ($stats['ok'] ?? 0);
$suspect = (int) ($stats['suspect'] ?? 0);
$fraud = (int) ($stats['fraud'] ?? 0);
$rateLimited = (int) ($stats['rate_limited'] ?? 0);

if ($fraud > 0) {
    $healthClass = 'error';
    $healthLabel = 'Є загрози';
} elseif ($suspect > 0 || $rateLimited > 0) {
    $healthClass = 'warn';
    $healthLabel = 'Під наглядом';
} else {
    $healthClass = 'ok';
    $healthLabel = 'Спокійно';
}

$hasFilters = array_filter($filters, static fn(string $v): bool => $v !== '') !== [];
?>

<div class="admin-security">
    <header class="admin-security__header">
        <div class="admin-security__header-main">
            <h1>Безпека</h1>
            <p class="admin-muted">Логи замовлень і входу · фільтр IP, браузера · скидання лімітів</p>
        </div>
        <div class="admin-security__health admin-security__health--<?= e($healthClass) ?>">
            <span class="admin-security__health-dot" aria-hidden="true"></span>
            <?= e($healthLabel) ?>
        </div>
    </header>

    <div class="admin-security__kpi">
        <div class="admin-security__kpi-item">
            <span class="admin-security__kpi-value"><?= $total ?></span>
            <span class="admin-security__kpi-label">Подій всього</span>
        </div>
        <div class="admin-security__kpi-item">
            <span class="admin-security__kpi-value admin-security__kpi-value--ok"><?= $ok ?></span>
            <span class="admin-security__kpi-label">Клієнти</span>
        </div>
        <div class="admin-security__kpi-item">
            <span class="admin-security__kpi-value admin-security__kpi-value--warn"><?= $suspect ?></span>
            <span class="admin-security__kpi-label">Підозріло</span>
        </div>
        <div class="admin-security__kpi-item">
            <span class="admin-security__kpi-value admin-security__kpi-value--error"><?= $fraud ?></span>
            <span class="admin-security__kpi-label">Шахраї</span>
        </div>
        <div class="admin-security__kpi-item">
            <span class="admin-security__kpi-value"><?= $rateLimited ?></span>
            <span class="admin-security__kpi-label">Ліміт / 24 год</span>
        </div>
    </div>

    <div class="admin-security__grid admin-security__grid--2">
        <section class="admin-card admin-security__panel">
            <div class="admin-security__panel-head">
                <span class="admin-security__panel-icon admin-security__panel-icon--limit" aria-hidden="true"></span>
                <div class="admin-security__panel-title">
                    <h2>Скинути ліміти</h2>
                    <p>Якщо клієнт бачить «Забагато спроб» — скиньте ліміт для його IP.</p>
                </div>
            </div>

            <form method="post" action="<?= admin_url('security/action') ?>" class="admin-security__ip-form">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="clear_rate_ip">
                <?php foreach ($filters as $key => $value): ?>
                <input type="hidden" name="filter_<?= e($key) ?>" value="<?= e($value) ?>">
                <?php endforeach; ?>
                <label class="admin-security__ip-field">
                    <span class="admin-security__field-label">IP адреса</span>
                    <input type="text" name="ip" value="<?= e($filters['ip']) ?>" placeholder="192.168.1.1" autocomplete="off">
                </label>
                <button type="submit" class="admin-btn admin-btn--telegram">Скинути IP</button>
            </form>

            <form method="post" action="<?= admin_url('security/action') ?>" class="admin-security__panel-action" onsubmit="return confirm('Скинути всі ліміти замовлень і входу?')">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="clear_rate_limits">
                <?php foreach ($filters as $key => $value): ?>
                <input type="hidden" name="filter_<?= e($key) ?>" value="<?= e($value) ?>">
                <?php endforeach; ?>
                <button type="submit" class="admin-btn admin-btn--outline admin-btn--block">Скинути всі ліміти</button>
            </form>
        </section>

        <section class="admin-card admin-security__panel admin-security__panel--danger">
            <div class="admin-security__panel-head">
                <span class="admin-security__panel-icon admin-security__panel-icon--logs" aria-hidden="true"></span>
                <div class="admin-security__panel-title">
                    <h2>Очистити логи</h2>
                    <p>Видалення старих записів зменшує розмір бази даних.</p>
                </div>
            </div>

            <div class="admin-security__panel-actions">
                <form method="post" action="<?= admin_url('security/action') ?>">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="action" value="clear_logs_30">
                    <?php foreach ($filters as $key => $value): ?>
                    <input type="hidden" name="filter_<?= e($key) ?>" value="<?= e($value) ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="admin-btn admin-btn--block">Старіші 30 днів</button>
                </form>
                <form method="post" action="<?= admin_url('security/action') ?>" onsubmit="return confirm('Видалити ВСІ логи безпеки?')">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="action" value="clear_logs_all">
                    <?php foreach ($filters as $key => $value): ?>
                    <input type="hidden" name="filter_<?= e($key) ?>" value="<?= e($value) ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="admin-btn admin-btn--danger admin-btn--block">Очистити все</button>
                </form>
            </div>
        </section>
    </div>

    <section class="admin-card admin-security__log">
        <div class="admin-security__log-head">
            <div>
                <h2>Журнал подій</h2>
                <?php if ($hasFilters): ?>
                <p class="admin-muted">Знайдено <?= count($events) ?> записів · <a href="<?= admin_url('security') ?>">скинути фільтри</a></p>
                <?php else: ?>
                <p class="admin-muted">Останні 150 подій</p>
                <?php endif; ?>
            </div>
        </div>

        <form method="get" action="<?= admin_url('security') ?>" class="admin-security__toolbar">
            <div class="admin-security__toolbar-fields">
                <label class="admin-security__toolbar-field">
                    <span class="admin-security__field-label">IP</span>
                    <input type="text" name="ip" value="<?= e($filters['ip']) ?>" placeholder="Частина IP">
                </label>
                <label class="admin-security__toolbar-field">
                    <span class="admin-security__field-label">Тип</span>
                    <select name="event_type">
                        <option value="">Усі</option>
                        <?php foreach ($eventLabels as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $filters['event_type'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="admin-security__toolbar-field">
                    <span class="admin-security__field-label">Оцінка</span>
                    <select name="verdict">
                        <option value="">Усі</option>
                        <?php foreach ($verdictLabels as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $filters['verdict'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="admin-security__toolbar-field admin-security__toolbar-field--wide">
                    <span class="admin-security__field-label">Браузер / шлях</span>
                    <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Chrome, /order, product_slug…">
                </label>
            </div>
            <div class="admin-security__toolbar-actions">
                <button type="submit" class="admin-btn">Фільтр</button>
                <a href="<?= admin_url('security') ?>" class="admin-btn admin-btn--ghost">Скинути</a>
            </div>
        </form>

        <?php if ($events === []): ?>
        <div class="admin-security__empty">
            <span class="admin-security__empty-icon" aria-hidden="true"></span>
            <strong>Подій не знайдено</strong>
            <p class="admin-muted"><?= $hasFilters ? 'Спробуйте інші фільтри або скиньте їх.' : 'Логи з’являться після замовлень, входу або спроб обходу лімітів.' ?></p>
        </div>
        <?php else: ?>
        <div class="admin-table-wrap admin-security__table-wrap">
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
                    $verdict = (string) ($event['verdict'] ?? '');
                    $eventType = (string) ($event['event_type'] ?? '');
                    $ip = (string) ($event['ip'] ?? '');
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
                    <tr class="admin-security__row admin-security__row--<?= e($verdict) ?>">
                        <td class="admin-security__time"><?= e($formatDt((string) ($event['created_at'] ?? ''))) ?></td>
                        <td><span class="admin-security__verdict admin-security__verdict--<?= e($verdict) ?>"><?= e($verdictLabels[$verdict] ?? $verdict) ?></span></td>
                        <td><span class="admin-security__event admin-security__event--<?= e($eventType) ?>"><?= e($eventLabels[$eventType] ?? $eventType) ?></span></td>
                        <td>
                            <a class="admin-security__ip" href="<?= admin_url('security', ['ip' => $ip]) ?>" title="Фільтр по IP"><?= e($ip) ?></a>
                        </td>
                        <td class="admin-security__browser" title="<?= e((string) ($event['user_agent'] ?? '')) ?>"><?= e($browser) ?></td>
                        <td class="admin-security__details"><?= e(implode(' · ', $details)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php
$statusLabels = ['new' => 'Нове', 'done' => 'Виконано', 'cancelled' => 'Скасовано'];
$analytics = $analytics ?? [];
$summary = $analytics['summary'] ?? ['sessions' => 0, 'page_views' => 0, 'avg_duration_sec' => 0, 'bounce_rate' => 0, 'clicks' => 0];
$chart = $analytics['chart'] ?? [];
$days = (int) ($analytics['days'] ?? 7);
$heatmapPath = (string) ($analytics['heatmap_path'] ?? '/');
$heatmap = $analytics['heatmap'] ?? [];
$topPages = $analytics['top_pages'] ?? [];
$recentSessions = $analytics['recent_sessions'] ?? [];

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

$chartMax = 1;
foreach ($chart as $point) {
    $chartMax = max($chartMax, (int) ($point['sessions'] ?? 0), (int) ($point['pageviews'] ?? 0));
}
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
            <p class="admin-muted">Власний трекінг без сторонніх сервісів · кліки, scroll, сесії, heatmap</p>
        </div>
        <form class="admin-analytics__filters" method="get" action="<?= admin_url('') ?>">
            <div class="admin-analytics__period" role="tablist" aria-label="Період">
                <?php foreach ([7 => '7 днів', 30 => '30 днів', 1 => 'Сьогодні'] as $period => $label): ?>
                <a
                    href="<?= admin_url('?days=' . $period . '&page=' . rawurlencode($heatmapPath)) ?>"
                    class="admin-analytics__period-btn<?= $days === $period ? ' is-active' : '' ?>"
                ><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </form>
    </div>

    <div class="admin-stats admin-stats--analytics">
        <div class="admin-stat admin-stat--ok">
            <span class="admin-stat__value"><?= (int) ($summary['sessions'] ?? 0) ?></span>
            <span class="admin-stat__label">Сесії</span>
        </div>
        <div class="admin-stat">
            <span class="admin-stat__value"><?= (int) ($summary['page_views'] ?? 0) ?></span>
            <span class="admin-stat__label">Перегляди</span>
        </div>
        <div class="admin-stat">
            <span class="admin-stat__value"><?= e($formatDuration((int) ($summary['avg_duration_sec'] ?? 0))) ?></span>
            <span class="admin-stat__label">Сер. час на сайті</span>
        </div>
        <div class="admin-stat admin-stat--warn">
            <span class="admin-stat__value"><?= e(number_format((float) ($summary['bounce_rate'] ?? 0), 1)) ?>%</span>
            <span class="admin-stat__label">Відмови (1 стор.)</span>
        </div>
        <div class="admin-stat">
            <span class="admin-stat__value"><?= (int) ($summary['clicks'] ?? 0) ?></span>
            <span class="admin-stat__label">Кліки</span>
        </div>
    </div>

    <div class="admin-analytics__grid">
        <section class="admin-analytics__panel">
            <h3>Динаміка</h3>
            <div class="admin-analytics__chart" data-analytics-chart>
                <?php foreach ($chart as $point): ?>
                <?php
                    $sessions = (int) ($point['sessions'] ?? 0);
                    $pageviews = (int) ($point['pageviews'] ?? 0);
                    $sessionH = max(4, (int) round(($sessions / $chartMax) * 100));
                    $pageH = max(4, (int) round(($pageviews / $chartMax) * 100));
                ?>
                <div class="admin-analytics__chart-col" title="<?= e(($point['date'] ?? '') . ': ' . $sessions . ' сесій, ' . $pageviews . ' переглядів') ?>">
                    <div class="admin-analytics__chart-bars">
                        <span class="admin-analytics__chart-bar admin-analytics__chart-bar--views" style="height: <?= $pageH ?>%"></span>
                        <span class="admin-analytics__chart-bar admin-analytics__chart-bar--sessions" style="height: <?= $sessionH ?>%"></span>
                    </div>
                    <span class="admin-analytics__chart-label"><?= e(substr((string) ($point['date'] ?? ''), 5)) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="admin-analytics__legend">
                <span><i class="admin-analytics__dot admin-analytics__dot--sessions"></i> Сесії</span>
                <span><i class="admin-analytics__dot admin-analytics__dot--views"></i> Перегляди</span>
            </div>
        </section>

        <section class="admin-analytics__panel admin-analytics__panel--heatmap">
            <div class="admin-analytics__panel-head">
                <h3>Heatmap кліків</h3>
                <form method="get" action="<?= admin_url('') ?>" class="admin-analytics__page-form">
                    <input type="hidden" name="days" value="<?= (int) $days ?>">
                    <select name="page" class="admin-input admin-analytics__page-select" onchange="this.form.submit()">
                        <?php if ($topPages === []): ?>
                        <option value="/">/</option>
                        <?php endif; ?>
                        <?php foreach ($topPages as $page): ?>
                        <option value="<?= e((string) $page['path']) ?>"<?= ($page['path'] ?? '') === $heatmapPath ? ' selected' : '' ?>>
                            <?= e((string) $page['path']) ?> (<?= (int) ($page['views'] ?? 0) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div
                class="admin-analytics__heatmap"
                data-analytics-heatmap
                data-heatmap="<?= e(json_encode($heatmap, JSON_UNESCAPED_UNICODE)) ?>"
            >
                <canvas class="admin-analytics__heatmap-canvas" width="640" height="360" aria-label="Heatmap кліків"></canvas>
                <div class="admin-analytics__heatmap-empty" hidden>Кліків ще немає для цієї сторінки</div>
            </div>
        </section>
    </div>

    <div class="admin-analytics__grid admin-analytics__grid--3">
        <?php
        $breakdownBlocks = [
            'Топ сторінки' => array_map(static fn(array $row): array => [
                'label' => (string) ($row['path'] ?? '/'),
                'count' => (int) ($row['views'] ?? 0),
            ], $topPages),
            'Браузери' => $analytics['browsers'] ?? [],
            'Пристрої' => $analytics['devices'] ?? [],
            'Мови' => $analytics['locales'] ?? [],
            'Джерела' => $analytics['referrers'] ?? [],
        ];
        ?>
        <?php foreach ($breakdownBlocks as $title => $items): ?>
        <section class="admin-analytics__panel admin-analytics__panel--compact">
            <h3><?= e($title) ?></h3>
            <?php if ($items === []): ?>
            <p class="admin-empty">Немає даних</p>
            <?php else: ?>
            <ul class="admin-analytics__bars">
                <?php
                $max = max(1, ...array_map(static fn(array $item): int => (int) ($item['count'] ?? 0), $items));
                foreach ($items as $item):
                    $count = (int) ($item['count'] ?? 0);
                    $width = max(6, (int) round(($count / $max) * 100));
                ?>
                <li>
                    <span class="admin-analytics__bar-label"><?= e((string) ($item['label'] ?? '—')) ?></span>
                    <span class="admin-analytics__bar-track"><span class="admin-analytics__bar-fill" style="width: <?= $width ?>%"></span></span>
                    <span class="admin-analytics__bar-value"><?= $count ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
    </div>

    <section class="admin-analytics__panel">
        <h3>Останні сесії</h3>
        <?php if ($recentSessions === []): ?>
        <p class="admin-empty">Відвідувачів ще не зафіксовано. Відкрийте сайт у новій вкладці — дані з’являться тут.</p>
        <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table admin-table--compact">
                <thead>
                    <tr>
                        <th>Час</th>
                        <th>IP</th>
                        <th>Пристрій</th>
                        <th>Сторінка входу</th>
                        <th>Перегляди</th>
                        <th>Час</th>
                        <th>Джерело</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentSessions as $session): ?>
                    <tr>
                        <td><?= e($formatDt((string) ($session['last_seen_at'] ?? ''))) ?></td>
                        <td><code><?= e((string) ($session['ip'] ?? '')) ?></code></td>
                        <td><?= e((string) (($session['device_type'] ?? '') . ' · ' . ($session['browser'] ?? ''))) ?></td>
                        <td><code><?= e((string) ($session['landing_path'] ?? '/')) ?></code></td>
                        <td><?= (int) ($session['page_views'] ?? 0) ?></td>
                        <td><?= e($formatDuration((int) ($session['duration_sec'] ?? 0))) ?></td>
                        <td><?= e($session['referrer'] !== '' ? parse_url((string) $session['referrer'], PHP_URL_HOST) ?: $session['referrer'] : 'Прямий') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
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

<script>
window.__FLOWAXY_ADMIN_ANALYTICS__ = <?= json_encode(['heatmap' => $heatmap], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= asset('assets/js/admin-dashboard.js') ?>" defer></script>

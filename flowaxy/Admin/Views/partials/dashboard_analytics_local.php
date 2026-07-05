<?php
/** @var array<string, mixed> $analytics */

$summary = $analytics['summary'] ?? ['sessions' => 0, 'page_views' => 0, 'avg_duration_sec' => 0, 'bounce_rate' => 0, 'clicks' => 0];
$chart = $analytics['chart'] ?? [];
$days = (int) ($analytics['days'] ?? 7);
$heatmapPath = (string) ($analytics['heatmap_path'] ?? '/');
$clickPages = $analytics['click_pages'] ?? [];
$topPages = $analytics['top_pages'] ?? [];
$recentSessions = $analytics['recent_sessions'] ?? [];
$heatmapUrl = admin_url('heatmap', ['days' => $days, 'page' => $heatmapPath, 'viewport' => 'desktop']);
$totalClickPages = count($clickPages);

$chartMax = 1;
foreach ($chart as $point) {
    $chartMax = max($chartMax, (int) ($point['sessions'] ?? 0), (int) ($point['pageviews'] ?? 0));
}
?>

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
        <span class="admin-stat__value"><?= e(format_duration((int) ($summary['avg_duration_sec'] ?? 0))) ?></span>
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

    <section class="admin-analytics__panel admin-analytics__panel--brief">
        <div class="admin-analytics__panel-head">
            <h3>Кліки</h3>
            <a href="<?= e($heatmapUrl) ?>" target="_blank" rel="noopener noreferrer" class="admin-btn admin-btn--sm">Heatmap ↗</a>
        </div>
        <?php if ($clickPages === []): ?>
        <p class="admin-empty">Кліків ще немає за обраний період.</p>
        <?php else: ?>
        <ul class="admin-analytics__brief-list">
            <?php foreach (array_slice($clickPages, 0, 5) as $page): ?>
            <li>
                <code><?= e((string) ($page['path'] ?? '/')) ?></code>
                <span><?= (int) ($page['clicks'] ?? 0) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($totalClickPages > 5): ?>
        <p class="admin-muted admin-analytics__brief-more">+<?= (int) ($totalClickPages - 5) ?> сторінок з кліками</p>
        <?php endif; ?>
        <?php endif; ?>
        <div class="admin-analytics__brief-foot">
            <span class="admin-muted"><?= (int) ($summary['clicks'] ?? 0) ?> кліків · <?= $totalClickPages ?> стор.</span>
            <a href="<?= e($heatmapUrl) ?>" target="_blank" rel="noopener noreferrer" class="admin-link">Відкрити heatmap на сайті ↗</a>
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
                    <td><?= e(format_datetime((string) ($session['last_seen_at'] ?? ''))) ?></td>
                    <td><code><?= e((string) ($session['ip'] ?? '')) ?></code></td>
                    <td><?= e((string) (($session['device_type'] ?? '') . ' · ' . ($session['browser'] ?? ''))) ?></td>
                    <td><code><?= e((string) ($session['landing_path'] ?? '/')) ?></code></td>
                    <td><?= (int) ($session['page_views'] ?? 0) ?></td>
                    <td><?= e(format_duration((int) ($session['duration_sec'] ?? 0))) ?></td>
                    <td><?= e($session['referrer'] !== '' ? parse_url((string) $session['referrer'], PHP_URL_HOST) ?: $session['referrer'] : 'Прямий') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

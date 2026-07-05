<?php
/** @var array<string, mixed> $googleReport */
/** @var callable $formatDuration */
/** @var callable $analyticsUrl */

$mode = (string) ($googleReport['mode'] ?? 'links');
$days = (int) ($googleReport['days'] ?? 7);
$gtmId = (string) ($googleReport['gtm_id'] ?? '');
$measurementId = (string) ($googleReport['measurement_id'] ?? '');
$error = (string) ($googleReport['error'] ?? '');
?>

<?php if ($error !== ''): ?>
<p class="admin-analytics__ga-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($mode === 'embed'): ?>
<div class="admin-analytics__embed-wrap">
    <iframe
        class="admin-analytics__embed"
        src="<?= e((string) ($googleReport['embed_url'] ?? '')) ?>"
        loading="lazy"
        allowfullscreen
        title="Google Analytics — Looker Studio"
    ></iframe>
</div>
<?php elseif ($mode === 'api'): ?>
<?php
$summary = $googleReport['summary'] ?? ['sessions' => 0, 'page_views' => 0, 'avg_duration_sec' => 0, 'bounce_rate' => 0, 'active_users' => 0];
$chart = $googleReport['chart'] ?? [];
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
        <span class="admin-stat__value"><?= e($formatDuration((int) ($summary['avg_duration_sec'] ?? 0))) ?></span>
        <span class="admin-stat__label">Сер. тривалість</span>
    </div>
    <div class="admin-stat admin-stat--warn">
        <span class="admin-stat__value"><?= e(number_format((float) ($summary['bounce_rate'] ?? 0), 1)) ?>%</span>
        <span class="admin-stat__label">Відмови</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__value"><?= (int) ($summary['active_users'] ?? 0) ?></span>
        <span class="admin-stat__label">Користувачі</span>
    </div>
</div>

<div class="admin-analytics__grid">
    <section class="admin-analytics__panel">
        <h3>Динаміка (GA4)</h3>
        <?php if ($chart === []): ?>
        <p class="admin-empty">Немає даних за обраний період</p>
        <?php else: ?>
        <div class="admin-analytics__chart">
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
        <?php endif; ?>
    </section>

    <section class="admin-analytics__panel">
        <h3>Google Analytics</h3>
        <p class="admin-muted">Дані з GA4 Data API · heatmap і кліки доступні лише у локальній вкладці.</p>
        <div class="admin-analytics__ga-links">
            <a href="https://analytics.google.com/" target="_blank" rel="noopener noreferrer" class="admin-btn admin-btn--ghost">Відкрити GA4 ↗</a>
            <a href="https://analytics.google.com/analytics/web/#/p<?= e(preg_replace('/\D/', '', (string) ($googleReport['property_id'] ?? ''))) ?>/reports/realtime" target="_blank" rel="noopener noreferrer" class="admin-btn admin-btn--ghost">Realtime ↗</a>
        </div>
    </section>
</div>

<div class="admin-analytics__grid admin-analytics__grid--3">
    <?php
    $topPages = $googleReport['top_pages'] ?? [];
    $breakdownBlocks = [
        'Топ сторінки' => $topPages,
        'Пристрої' => $googleReport['devices'] ?? [],
        'Канали' => $googleReport['sources'] ?? [],
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
<?php else: ?>
<div class="admin-analytics__ga-fallback">
    <p class="admin-muted">Трекінг активний<?php if ($gtmId !== ''): ?> · GTM <code><?= e($gtmId) ?></code><?php endif; ?><?php if ($measurementId !== ''): ?> · GA4 <code><?= e($measurementId) ?></code><?php endif; ?>.</p>
    <p>Для звітів прямо в адмінці додайте в <code>.env</code> один із варіантів:</p>
    <ul class="admin-analytics__ga-setup">
        <li><code>GA4_LOOKER_EMBED_URL</code> — embed-звіт Looker Studio</li>
        <li><code>GA4_PROPERTY_ID</code> + <code>GA4_SERVICE_ACCOUNT_JSON</code> — GA4 Data API</li>
    </ul>
    <div class="admin-analytics__ga-links">
        <a href="https://analytics.google.com/" target="_blank" rel="noopener noreferrer" class="admin-btn">Відкрити Google Analytics ↗</a>
        <a href="https://tagmanager.google.com/" target="_blank" rel="noopener noreferrer" class="admin-btn admin-btn--ghost">Google Tag Manager ↗</a>
    </div>
</div>
<?php endif; ?>

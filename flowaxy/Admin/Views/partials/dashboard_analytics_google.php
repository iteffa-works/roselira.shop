<?php
/** @var array<string, mixed> $googleReport */

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
$isRealtime = !empty($googleReport['realtime']);
$live = $googleReport['live'] ?? null;
$summary = $googleReport['summary'] ?? ['sessions' => 0, 'page_views' => 0, 'avg_duration_sec' => 0, 'bounce_rate' => 0, 'active_users' => 0];
$historicalEmpty = ((int) ($summary['sessions'] ?? 0)) === 0
    && ((int) ($summary['page_views'] ?? 0)) === 0
    && ((int) ($summary['active_users'] ?? 0)) === 0;
$liveActive = (int) ($live['active_users'] ?? ($isRealtime ? ($summary['active_users'] ?? 0) : 0));
$chart = $googleReport['chart'] ?? [];
$chartMax = 1;
foreach ($chart as $point) {
    $chartMax = max($chartMax, (int) ($point['sessions'] ?? 0), (int) ($point['pageviews'] ?? 0));
}
?>
<div
    class="admin-analytics__ga-api<?= $isRealtime ? ' admin-analytics__ga-api--realtime' : '' ?>"
    data-ga-poll
    data-ga-api-url="<?= e(admin_url('api/google-analytics?days=' . $days)) ?>"
    data-ga-live-url="<?= e(admin_url('api/google-analytics?live=1')) ?>"
    data-ga-poll-mode="<?= $isRealtime ? 'full' : 'live' ?>"
>
<?php if ($isRealtime): ?>
<p class="admin-analytics__realtime-badge"><span class="admin-analytics__realtime-dot" aria-hidden="true"></span> Realtime · оновлення кожні 30 с</p>
<?php elseif ($liveActive > 0 || is_array($live)): ?>
<p class="admin-analytics__live-strip">
    <span class="admin-analytics__realtime-dot" aria-hidden="true"></span>
    Активні зараз: <strong data-ga-live-active><?= $liveActive ?></strong>
    · оновлення кожні 30 с
</p>
<?php endif; ?>
<?php if (!$isRealtime && $historicalEmpty && $liveActive > 0): ?>
<p class="admin-analytics__ga-notice" role="status">
    Стандартні звіти GA4 (7/30 днів) з’являються з затримкою <strong>24–48 год</strong> — це нормально для нового трекінгу.
    Realtime-дані вже надходять · перейдіть на вкладку
    <a href="<?= e(admin_url('?source=google&days=1')) ?>">Сьогодні</a>.
</p>
<?php endif; ?>
<div class="admin-stats admin-stats--analytics">
    <div class="admin-stat admin-stat--ok">
        <span class="admin-stat__value" data-ga-stat="active_users"><?= (int) ($summary['active_users'] ?? 0) ?></span>
        <span class="admin-stat__label"><?= $isRealtime ? 'Активні зараз' : 'Сесії' ?></span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__value" data-ga-stat="page_views"><?= (int) ($summary['page_views'] ?? 0) ?></span>
        <span class="admin-stat__label"><?= $isRealtime ? 'Події' : 'Перегляди' ?></span>
    </div>
    <?php if (!$isRealtime): ?>
    <div class="admin-stat">
        <span class="admin-stat__value"><?= e(format_duration((int) ($summary['avg_duration_sec'] ?? 0))) ?></span>
        <span class="admin-stat__label">Сер. тривалість</span>
    </div>
    <div class="admin-stat admin-stat--warn">
        <span class="admin-stat__value"><?= e(number_format((float) ($summary['bounce_rate'] ?? 0), 1)) ?>%</span>
        <span class="admin-stat__label">Відмови</span>
    </div>
    <?php endif; ?>
    <div class="admin-stat">
        <span class="admin-stat__value" data-ga-stat="sessions"><?= (int) ($summary['sessions'] ?? 0) ?></span>
        <span class="admin-stat__label"><?= $isRealtime ? 'Усі активні' : 'Користувачі' ?></span>
    </div>
</div>

<div class="admin-analytics__grid">
    <section class="admin-analytics__panel">
        <h3><?= $isRealtime ? 'Realtime · останні 30 хв' : 'Динаміка (GA4)' ?></h3>
        <div data-ga-chart>
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
            <div class="admin-analytics__chart-col" title="<?= e(($point['date'] ?? '') . ': ' . $sessions . ($isRealtime ? ' активних' : ' сесій')) ?>">
                <div class="admin-analytics__chart-bars">
                    <?php if (!$isRealtime): ?>
                    <span class="admin-analytics__chart-bar admin-analytics__chart-bar--views" style="height: <?= $pageH ?>%"></span>
                    <?php endif; ?>
                    <span class="admin-analytics__chart-bar admin-analytics__chart-bar--sessions" style="height: <?= $sessionH ?>%"></span>
                </div>
                <span class="admin-analytics__chart-label"><?= e($isRealtime ? (string) ($point['date'] ?? '') : substr((string) ($point['date'] ?? ''), 5)) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$isRealtime): ?>
        <div class="admin-analytics__legend">
            <span><i class="admin-analytics__dot admin-analytics__dot--sessions"></i> Сесії</span>
            <span><i class="admin-analytics__dot admin-analytics__dot--views"></i> Перегляди</span>
        </div>
        <?php else: ?>
        <div class="admin-analytics__legend">
            <span><i class="admin-analytics__dot admin-analytics__dot--sessions"></i> Активні користувачі</span>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        </div>
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

    <div class="admin-analytics__grid admin-analytics__grid--3" data-ga-breakdowns>
    <?php
    $topPages = $googleReport['top_pages'] ?? [];
    $breakdownBlocks = [
        'Топ сторінки' => $topPages,
        'Пристрої' => $googleReport['devices'] ?? [],
        ($isRealtime ? 'Країни' : 'Канали') => $googleReport['sources'] ?? [],
    ];
    ?>
    <?php foreach ($breakdownBlocks as $title => $items): ?>
    <section class="admin-analytics__panel admin-analytics__panel--compact" data-ga-breakdown="<?= e($title) ?>">
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

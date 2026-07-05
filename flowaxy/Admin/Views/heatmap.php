<?php
/** @var array<string, mixed> $analytics */

use Flowaxy\Support\HeatmapViewport;

$days = (int) ($analytics['days'] ?? 7);
$viewport = (string) ($analytics['viewport'] ?? 'desktop');
$heatmapPath = (string) ($analytics['heatmap_path'] ?? '/');
$heatmap = $analytics['heatmap'] ?? [];
$heatmapPreviewW = (int) ($analytics['heatmap_preview_w'] ?? 1280);
$clickPages = $analytics['click_pages'] ?? [];
$topPages = $analytics['top_pages'] ?? [];
$clickCount = (int) ($analytics['heatmap_click_count'] ?? count($heatmap));
$viewportCounts = $analytics['viewport_counts'] ?? [];
$viewportLabel = (string) ($analytics['viewport_label'] ?? HeatmapViewport::profile($viewport)['label']);
$viewportProfile = HeatmapViewport::profile($viewport);
$heatmapJson = json_encode($heatmap, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($heatmapJson === false) {
    $heatmapJson = '[]';
}

$heatmapQuery = static function (array $overrides = []) use ($days, $heatmapPath, $viewport): string {
    return admin_url('heatmap', array_merge([
        'days' => $days,
        'page' => $heatmapPath,
        'viewport' => $viewport,
    ], $overrides));
};
?>

<section class="admin-card admin-heatmap-tool">
    <div class="admin-heatmap-toolbar">
        <div class="admin-analytics__viewport" role="tablist" aria-label="Пристрій">
            <?php foreach (HeatmapViewport::PROFILES as $id => $profile): ?>
            <?php
                $rangeLabel = $profile['max'] >= 9999 ? $profile['min'] . '+' : $profile['min'] . '–' . $profile['max'];
                $profileCount = (int) ($viewportCounts[$id] ?? 0);
            ?>
            <a
                href="<?= e($heatmapQuery(['viewport' => $id])) ?>"
                class="admin-analytics__viewport-btn<?= $viewport === $id ? ' is-active' : '' ?>"
                role="tab"
                title="<?= e($profile['label'] . ' · ' . $rangeLabel . 'px · ' . $profileCount . ' кліків') ?>"
            ><?= e($profile['label']) ?> <span><?= $profileCount ?></span></a>
            <?php endforeach; ?>
        </div>
        <div class="admin-analytics__period" role="tablist" aria-label="Період">
            <?php foreach ([7 => '7 днів', 30 => '30 днів', 1 => 'Сьогодні'] as $period => $label): ?>
            <a
                href="<?= e($heatmapQuery(['days' => $period])) ?>"
                class="admin-analytics__period-btn<?= $days === $period ? ' is-active' : '' ?>"
            ><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
        <select
            class="admin-input admin-analytics__page-select admin-heatmap-toolbar__page"
            aria-label="Сторінка"
            onchange="window.location.href='<?= e(admin_url('heatmap')) ?>?days=<?= (int) $days ?>&viewport=<?= e(rawurlencode($viewport)) ?>&page=' + encodeURIComponent(this.value)"
        >
            <?php if ($clickPages === []): ?>
            <option value="<?= e($heatmapPath) ?>"><?= e($heatmapPath) ?> (0)</option>
            <?php endif; ?>
            <?php foreach ($clickPages as $page): ?>
            <option value="<?= e((string) $page['path']) ?>"<?= ($page['path'] ?? '') === $heatmapPath ? ' selected' : '' ?>>
                <?= e((string) $page['path']) ?> (<?= (int) ($page['clicks'] ?? 0) ?>)
            </option>
            <?php endforeach; ?>
            <?php foreach ($topPages as $page): ?>
            <?php if (array_filter($clickPages, static fn(array $row): bool => ($row['path'] ?? '') === ($page['path'] ?? '')) !== []): ?>
            <?php continue; ?>
            <?php endif; ?>
            <option value="<?= e((string) $page['path']) ?>"<?= ($page['path'] ?? '') === $heatmapPath ? ' selected' : '' ?>>
                <?= e((string) $page['path']) ?> (0 · <?= (int) ($page['views'] ?? 0) ?> перегл.)
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <script type="application/json" id="heatmap-points-data"><?= $heatmapJson ?></script>
    <div
        class="admin-analytics__heatmap admin-analytics__heatmap--full admin-analytics__heatmap--<?= e($viewport) ?>"
        data-analytics-heatmap
        data-heatmap-id="heatmap-points-data"
        data-page-path="<?= e($heatmapPath) ?>"
        data-preview-width="<?= (int) $heatmapPreviewW ?>"
    >
        <div class="admin-analytics__heatmap-scroll">
            <div class="admin-device-frame admin-device-frame--<?= e($viewport) ?>">
                <?php if ($viewport === 'desktop'): ?>
                <div class="admin-device-frame__chrome" aria-hidden="true">
                    <span></span><span></span><span></span>
                </div>
                <?php elseif ($viewport === 'mobile'): ?>
                <div class="admin-device-frame__notch" aria-hidden="true"></div>
                <?php endif; ?>
                <div class="admin-device-frame__screen">
                    <div class="admin-analytics__heatmap-stage">
                        <iframe
                            class="admin-analytics__heatmap-frame"
                            title="Попередній перегляд сторінки для heatmap"
                            loading="lazy"
                            tabindex="-1"
                        ></iframe>
                        <canvas class="admin-analytics__heatmap-overlay" aria-hidden="true"></canvas>
                    </div>
                </div>
                <?php if ($viewport === 'desktop'): ?>
                <div class="admin-device-frame__stand" aria-hidden="true"><span></span></div>
                <?php elseif ($viewport === 'mobile'): ?>
                <div class="admin-device-frame__home" aria-hidden="true"></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="admin-analytics__heatmap-loading">Завантаження сторінки…</div>
        <div class="admin-analytics__heatmap-empty"<?= $heatmap !== [] ? ' hidden' : '' ?>>
            Немає кліків · <?= e($viewportLabel) ?> <?= (int) $viewportProfile['preview'] ?>px
        </div>
    </div>

    <p class="admin-analytics__heatmap-meta">
        <code><?= e($heatmapPath) ?></code>
        · <?= e($viewportLabel) ?> <?= (int) $viewportProfile['preview'] ?>px
        · <?= $clickCount ?> кліків
        · <?= (int) $days ?> дн.
    </p>
</section>

<script src="<?= asset('assets/js/admin-dashboard.js') ?>" defer></script>

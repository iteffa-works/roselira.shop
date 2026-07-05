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

<?php
$cleanupContext = [
    'csrf' => (string) ($csrf ?? ''),
    'days' => $days,
    'page' => $heatmapPath,
    'viewport' => $viewport,
];
$cleanupForm = static function (
    string $scope,
    int $periodDays,
    string $label,
    string $confirm,
    bool $danger = false,
) use ($cleanupContext): void {
    $btnClass = 'admin-btn admin-btn--sm' . ($danger ? ' admin-btn--danger' : '');
    ?>
    <form
        method="post"
        action="<?= e(admin_url('heatmap/cleanup')) ?>"
        class="admin-heatmap-cleanup__action"
        onsubmit="return confirm(<?= json_encode($confirm, JSON_UNESCAPED_UNICODE) ?>)"
    >
        <input type="hidden" name="csrf" value="<?= e($cleanupContext['csrf']) ?>">
        <input type="hidden" name="scope" value="<?= e($scope) ?>">
        <input type="hidden" name="period_days" value="<?= (int) $periodDays ?>">
        <input type="hidden" name="days" value="<?= (int) $cleanupContext['days'] ?>">
        <input type="hidden" name="page" value="<?= e($cleanupContext['page']) ?>">
        <input type="hidden" name="viewport" value="<?= e($cleanupContext['viewport']) ?>">
        <label class="admin-heatmap-cleanup__filter">
            <input type="checkbox" name="filter_page" value="1" checked>
            <span>Лише ця сторінка</span>
        </label>
        <label class="admin-heatmap-cleanup__filter">
            <input type="checkbox" name="filter_viewport" value="1" checked>
            <span>Лише <?= e($cleanupContext['viewport']) ?></span>
        </label>
        <label class="admin-heatmap-cleanup__filter">
            <input type="checkbox" name="clicks_only" value="1" checked>
            <span>Лише кліки</span>
        </label>
        <button type="submit" class="<?= e($btnClass) ?>"><?= e($label) ?></button>
    </form>
    <?php
};
?>

<details class="admin-card admin-card--danger admin-heatmap-cleanup">
    <summary class="admin-heatmap-cleanup__summary">Очистити дані аналітики</summary>
    <p class="admin-muted admin-card__desc">Незворотне видалення подій і порожніх сесій. Зніміть фільтри, щоб очистити всю локальну аналітику.</p>

    <div class="admin-heatmap-cleanup__presets">
        <?php $cleanupForm('within_last', 1, 'За останній день', 'Видалити дані за останній день?'); ?>
        <?php $cleanupForm('within_last', 7, 'За 7 днів', 'Видалити дані за останні 7 днів?'); ?>
        <?php $cleanupForm('older_than', 30, 'Старіші за 30 днів', 'Видалити дані старіші за 30 днів?'); ?>
        <?php $cleanupForm('all', 0, 'За все час', 'УВАГА: видалити всі відповідні дані?', true); ?>
    </div>

    <form
        method="post"
        action="<?= e(admin_url('heatmap/cleanup')) ?>"
        class="admin-heatmap-cleanup__custom"
        onsubmit="return confirm('Видалити дані за обраним періодом?')"
    >
        <input type="hidden" name="csrf" value="<?= e($cleanupContext['csrf']) ?>">
        <input type="hidden" name="days" value="<?= (int) $cleanupContext['days'] ?>">
        <input type="hidden" name="page" value="<?= e($cleanupContext['page']) ?>">
        <input type="hidden" name="viewport" value="<?= e($cleanupContext['viewport']) ?>">
        <label class="admin-heatmap-cleanup__filter">
            <input type="checkbox" name="filter_page" value="1" checked>
            <span>Лише ця сторінка</span>
        </label>
        <label class="admin-heatmap-cleanup__filter">
            <input type="checkbox" name="filter_viewport" value="1" checked>
            <span>Лише <?= e($cleanupContext['viewport']) ?></span>
        </label>
        <label class="admin-heatmap-cleanup__filter">
            <input type="checkbox" name="clicks_only" value="1" checked>
            <span>Лише кліки</span>
        </label>
        <div class="admin-heatmap-cleanup__custom-row">
            <select name="scope" class="admin-input admin-input-sm" aria-label="Тип періоду">
                <option value="within_last">За останні N днів</option>
                <option value="older_than">Старіші за N днів</option>
            </select>
            <input
                type="number"
                name="period_days"
                class="admin-input admin-input-sm admin-heatmap-cleanup__days"
                min="1"
                max="3650"
                value="14"
                aria-label="Кількість днів"
            >
            <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">Видалити</button>
        </div>
    </form>
</details>

<script src="<?= asset('assets/js/admin-dashboard.js') ?>" defer></script>

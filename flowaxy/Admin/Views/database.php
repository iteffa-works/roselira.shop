<?php
$statusLabels = order_status_labels(true);
$dbSizeKb = round($dbSize / 1024, 1);
?>

<div class="admin-page-header">
    <h1>База даних</h1>
    <p class="admin-muted">Очищення та обслуговування SQLite</p>
</div>

<div class="admin-stats">
    <div class="admin-stat">
        <span class="admin-stat__value"><?= (int) $counts['orders'] ?></span>
        <span class="admin-stat__label">Замовлень</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__value"><?= (int) $counts['products'] ?></span>
        <span class="admin-stat__label">Товарів</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__value"><?= (int) $counts['locale_strings'] ?></span>
        <span class="admin-stat__label">UI-текстів</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__value"><?= e((string) $dbSizeKb) ?></span>
        <span class="admin-stat__label">КБ (розмір БД)</span>
    </div>
</div>

<div class="admin-grid">
    <section class="admin-card">
        <h2 class="admin-card__title">Замовлення за статусом</h2>
        <ul class="admin-list">
            <?php foreach ($statuses as $status): ?>
            <li>
                <span><?= e($statusLabels[$status] ?? $status) ?></span>
                <strong><?= (int) ($orderCounts[$status] ?? 0) ?></strong>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="admin-card admin-card--danger">
        <h2 class="admin-card__title">Очистити замовлення</h2>
        <p class="admin-muted admin-card__desc">Дії незворотні. Нові замовлення не видаляються автоматично.</p>

        <form method="post" class="admin-actions" onsubmit="return confirm('Видалити скасовані замовлення?')">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="delete_cancelled">
            <button type="submit" class="admin-btn admin-btn--danger admin-btn--block">Видалити скасовані</button>
        </form>

        <form method="post" class="admin-actions" onsubmit="return confirm('Видалити виконані замовлення?')">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="delete_done">
            <button type="submit" class="admin-btn admin-btn--danger admin-btn--block">Видалити виконані</button>
        </form>

        <form method="post" class="admin-actions" onsubmit="return confirm('Видалити всі закриті (виконані + скасовані)?')">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="delete_closed">
            <button type="submit" class="admin-btn admin-btn--danger admin-btn--block">Видалити закриті</button>
        </form>

        <form method="post" class="admin-actions" onsubmit="return confirm('УВАГА: видалити ВСІ замовлення без повернення?')">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="delete_all_orders">
            <button type="submit" class="admin-btn admin-btn--danger admin-btn--block">Очистити всі замовлення</button>
        </form>

        <?php
        $orderCleanupForm = static function (
            string $scope,
            int $periodDays,
            string $label,
            string $confirm,
            bool $danger = false,
        ) use ($csrf, $statuses, $statusLabels): void {
            $btnClass = 'admin-btn admin-btn--sm' . ($danger ? ' admin-btn--danger' : '');
            ?>
            <form
                method="post"
                class="admin-db-cleanup__action"
                onsubmit="return confirm(<?= json_encode($confirm, JSON_UNESCAPED_UNICODE) ?>)"
            >
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="purge_orders">
                <input type="hidden" name="scope" value="<?= e($scope) ?>">
                <input type="hidden" name="period_days" value="<?= (int) $periodDays ?>">
                <label class="admin-db-cleanup__filter">
                    <input type="checkbox" name="filter_status" value="1" checked>
                    <span>Лише статуси:</span>
                </label>
                <?php foreach ($statuses as $status): ?>
                <label class="admin-db-cleanup__filter">
                    <input
                        type="checkbox"
                        name="status_<?= e($status) ?>"
                        value="1"
                        <?= in_array($status, ['done', 'cancelled'], true) ? 'checked' : '' ?>
                    >
                    <span><?= e($statusLabels[$status] ?? $status) ?></span>
                </label>
                <?php endforeach; ?>
                <button type="submit" class="<?= e($btnClass) ?>"><?= e($label) ?></button>
            </form>
            <?php
        };
        ?>

        <details class="admin-db-cleanup">
            <summary class="admin-db-cleanup__summary">За періодом</summary>
            <p class="admin-muted admin-card__desc">Зніміть фільтр статусів — видаляться всі замовлення за періодом, включно з новими.</p>
            <div class="admin-db-cleanup__presets">
                <?php $orderCleanupForm('within_last', 1, 'За останній день', 'Видалити замовлення за останній день?'); ?>
                <?php $orderCleanupForm('within_last', 7, 'За 7 днів', 'Видалити замовлення за останні 7 днів?'); ?>
                <?php $orderCleanupForm('older_than', 30, 'Старіші за 30 днів', 'Видалити замовлення старіші за 30 днів?'); ?>
                <?php $orderCleanupForm('all', 0, 'За весь час', 'УВАГА: видалити всі замовлення за обраними статусами?', true); ?>
            </div>
            <form
                method="post"
                class="admin-db-cleanup__custom"
                onsubmit="return confirm('Видалити замовлення за обраним періодом?')"
            >
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="purge_orders">
                <label class="admin-db-cleanup__filter">
                    <input type="checkbox" name="filter_status" value="1" checked>
                    <span>Лише статуси:</span>
                </label>
                <?php foreach ($statuses as $status): ?>
                <label class="admin-db-cleanup__filter">
                    <input
                        type="checkbox"
                        name="status_<?= e($status) ?>"
                        value="1"
                        <?= in_array($status, ['done', 'cancelled'], true) ? 'checked' : '' ?>
                    >
                    <span><?= e($statusLabels[$status] ?? $status) ?></span>
                </label>
                <?php endforeach; ?>
                <div class="admin-db-cleanup__custom-row">
                    <select name="scope" class="admin-input admin-input-sm" aria-label="Тип періоду">
                        <option value="within_last">За останні N днів</option>
                        <option value="older_than">Старіші за N днів</option>
                    </select>
                    <input
                        type="number"
                        name="period_days"
                        class="admin-input admin-input-sm admin-db-cleanup__days"
                        min="1"
                        max="3650"
                        value="14"
                        aria-label="Кількість днів"
                    >
                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">Видалити</button>
                </div>
            </form>
        </details>
    </section>

    <section class="admin-card admin-card--danger">
        <h2 class="admin-card__title">Очистити аналітику</h2>
        <p class="admin-muted admin-card__desc">Локальні події heatmap і сесії відвідувачів. GA4 не зачіпається.</p>

        <?php
        $analyticsCleanupForm = static function (
            string $scope,
            int $periodDays,
            string $label,
            string $confirm,
            bool $danger = false,
        ) use ($csrf): void {
            $btnClass = 'admin-btn admin-btn--sm' . ($danger ? ' admin-btn--danger' : '');
            ?>
            <form
                method="post"
                class="admin-db-cleanup__action"
                onsubmit="return confirm(<?= json_encode($confirm, JSON_UNESCAPED_UNICODE) ?>)"
            >
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="purge_analytics">
                <input type="hidden" name="scope" value="<?= e($scope) ?>">
                <input type="hidden" name="period_days" value="<?= (int) $periodDays ?>">
                <label class="admin-db-cleanup__filter">
                    <input type="checkbox" name="clicks_only" value="1">
                    <span>Лише кліки</span>
                </label>
                <button type="submit" class="<?= e($btnClass) ?>"><?= e($label) ?></button>
            </form>
            <?php
        };
        ?>

        <div class="admin-db-cleanup__presets">
            <?php $analyticsCleanupForm('within_last', 1, 'За останній день', 'Видалити аналітику за останній день?'); ?>
            <?php $analyticsCleanupForm('within_last', 7, 'За 7 днів', 'Видалити аналітику за останні 7 днів?'); ?>
            <?php $analyticsCleanupForm('older_than', 30, 'Старіші за 30 днів', 'Видалити аналітику старіші за 30 днів?'); ?>
            <?php $analyticsCleanupForm('all', 0, 'За весь час', 'УВАГА: видалити всю локальну аналітику?', true); ?>
        </div>
        <form
            method="post"
            class="admin-db-cleanup__custom"
            onsubmit="return confirm('Видалити аналітику за обраним періодом?')"
        >
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="purge_analytics">
            <label class="admin-db-cleanup__filter">
                <input type="checkbox" name="clicks_only" value="1">
                <span>Лише кліки</span>
            </label>
            <div class="admin-db-cleanup__custom-row">
                <select name="scope" class="admin-input admin-input-sm" aria-label="Тип періоду">
                    <option value="within_last">За останні N днів</option>
                    <option value="older_than">Старіші за N днів</option>
                </select>
                <input
                    type="number"
                    name="period_days"
                    class="admin-input admin-input-sm admin-db-cleanup__days"
                    min="1"
                    max="3650"
                    value="14"
                    aria-label="Кількість днів"
                >
                <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">Видалити</button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2 class="admin-card__title">Обслуговування</h2>
        <p class="admin-muted admin-card__desc">Після масового видалення рекомендується оптимізувати базу.</p>
        <form method="post" class="admin-actions" onsubmit="return confirm('Запустити VACUUM для зменшення розміру файлу БД?')">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="vacuum">
            <button type="submit" class="admin-btn admin-btn--block">Оптимізувати БД (VACUUM)</button>
        </form>
    </section>
</div>

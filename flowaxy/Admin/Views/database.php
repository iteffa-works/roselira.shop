<?php
$statusLabels = ['new' => 'Нові', 'done' => 'Виконані', 'cancelled' => 'Скасовані'];
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

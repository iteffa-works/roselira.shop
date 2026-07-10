<?php
/** @var array<string, array<string, mixed>> $categories */
/** @var array<string, int> $usage */
/** @var array<string, mixed>|null $edit */
$isEdit = is_array($edit);
$editLabels = is_array($edit['labels'] ?? null) ? $edit['labels'] : [];
?>
<h1>Категорії каталогу</h1>
<p class="admin-muted">Назви для вітрини та Google Product Category ID для Merchant listings. <a href="<?= admin_url('catalog') ?>">← Каталог</a></p>

<div class="admin-table-wrap">
<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>UK</th>
            <th>RU</th>
            <th>EN</th>
            <th>GPC</th>
            <th>Порядок</th>
            <th>Товарів</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php if ($categories === []): ?>
        <tr><td colspan="8" class="admin-muted">Ще немає категорій — створіть нижче.</td></tr>
    <?php else: ?>
        <?php foreach ($categories as $id => $category): ?>
            <?php $labels = is_array($category['labels'] ?? null) ? $category['labels'] : []; ?>
            <tr>
                <td><code class="admin-code"><?= e((string) $id) ?></code></td>
                <td><?= e((string) ($labels['uk'] ?? '')) ?></td>
                <td><?= e((string) ($labels['ru'] ?? '')) ?></td>
                <td><?= e((string) ($labels['en'] ?? '')) ?></td>
                <td><?= e((string) ($category['google_product_category'] ?? '') ?: '—') ?></td>
                <td><?= (int) ($category['order'] ?? 999) ?></td>
                <td><?= (int) ($usage[$id] ?? 0) ?></td>
                <td class="admin-table__actions">
                    <a href="<?= admin_url('categories', ['edit' => (string) $id]) ?>">Редагувати</a>
                    <form method="post" class="admin-inline-form" onsubmit="return confirm('Видалити категорію <?= e((string) $id) ?>?')">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= e((string) $id) ?>">
                        <button type="submit" class="admin-link-btn">Видалити</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>

<form method="post" class="admin-form admin-form--wide" style="margin-top:1.5rem">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="save">
    <?php if ($isEdit): ?>
    <input type="hidden" name="original_id" value="<?= e((string) ($edit['id'] ?? '')) ?>">
    <?php endif; ?>

    <fieldset>
        <legend><?= $isEdit ? 'Редагувати категорію' : 'Нова категорія' ?></legend>
        <label>ID
            <input type="text" name="id" value="<?= e((string) ($edit['id'] ?? '')) ?>" required pattern="[a-z0-9_-]+" placeholder="lips" <?= $isEdit ? '' : '' ?>>
            <span class="admin-field__hint">Латиниця, цифри, дефіс. Приклад: <code>lips</code>, <code>face</code></span>
        </label>
        <label>Назва UK <input type="text" name="label_uk" value="<?= e((string) ($editLabels['uk'] ?? '')) ?>" placeholder="Губи"></label>
        <label>Назва RU <input type="text" name="label_ru" value="<?= e((string) ($editLabels['ru'] ?? '')) ?>" placeholder="Губы"></label>
        <label>Назва EN <input type="text" name="label_en" value="<?= e((string) ($editLabels['en'] ?? '')) ?>" placeholder="Lips"></label>
        <label>Google Product Category (ID)
            <input type="text" name="google_product_category" value="<?= e((string) ($edit['google_product_category'] ?? '')) ?>" placeholder="2975" inputmode="numeric">
            <span class="admin-field__hint">ID з <a href="https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt" target="_blank" rel="noopener">таксономії Google</a></span>
        </label>
        <label>Порядок
            <input type="number" name="order" value="<?= e((string) ($edit['order'] ?? '10')) ?>" min="1" step="1">
        </label>
    </fieldset>

    <div class="admin-actions">
        <button type="submit" class="admin-btn"><?= $isEdit ? 'Зберегти' : 'Створити' ?></button>
        <?php if ($isEdit): ?>
        <a href="<?= admin_url('categories') ?>" class="admin-btn admin-btn--ghost">Скасувати</a>
        <?php endif; ?>
    </div>
</form>

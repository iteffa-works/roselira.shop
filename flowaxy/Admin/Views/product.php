<h1>Редагування: <code><?= e($slug) ?></code></h1>
<p><a href="/<?= e($slug) ?>" target="_blank">Відкрити на сайті</a> · <a href="<?= admin_url('catalog') ?>">← Каталог</a></p>

<form method="post" class="admin-form admin-form--wide">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="slug" value="<?= e($slug) ?>">

    <fieldset>
        <legend>Загальне</legend>
        <label class="admin-checkbox"><input type="checkbox" name="active" <?= ($product['active'] ?? false) ? 'checked' : '' ?>> Товар активний</label>
        <label>Default variant
            <input type="text" name="default_variant" value="<?= e((string) ($product['default_variant'] ?? '')) ?>">
        </label>
    </fieldset>

    <?php foreach ($editableLocales as $loc): ?>
    <?php $i18n = $product['i18n'][$loc] ?? []; ?>
    <?php $label = $localeLabels[$loc] ?? strtoupper($loc); ?>
    <fieldset>
        <legend><?= e($label) ?></legend>
        <label>Назва <input type="text" name="name_<?= e($loc) ?>" value="<?= e((string) ($i18n['name'] ?? '')) ?>"></label>
        <label>Короткий опис <textarea name="short_desc_<?= e($loc) ?>" rows="2"><?= e((string) ($i18n['short_desc'] ?? '')) ?></textarea></label>
        <label>Переваги (по одній на рядок) <textarea name="benefits_<?= e($loc) ?>" rows="4"><?= e(implode("\n", $i18n['benefits'] ?? [])) ?></textarea></label>
        <label>Повний опис <textarea name="description_<?= e($loc) ?>" rows="6"><?= e((string) ($i18n['description'] ?? '')) ?></textarea></label>
    </fieldset>
    <?php endforeach; ?>

    <fieldset>
        <legend>Варіанти (курс EUR <?= e(number_format($rates['EUR'], 4)) ?>, USD <?= e(number_format($rates['USD'], 4)) ?>)</legend>
        <table class="admin-table">
            <thead>
                <tr><th>ID</th><th>Active</th><th>EUR</th><th>USD</th><th>UAH</th><th>URL</th></tr>
            </thead>
            <tbody>
            <?php foreach ($product['variants'] ?? [] as $variant): ?>
                <?php $vid = (string) ($variant['id'] ?? ''); ?>
                <tr>
                    <td>
                        <input type="hidden" name="variant_id[]" value="<?= e($vid) ?>">
                        <code><?= e($vid) ?></code>
                    </td>
                    <td><input type="checkbox" name="variant_active[<?= e($vid) ?>]" <?= ($variant['active'] ?? true) ? 'checked' : '' ?>></td>
                    <td><input type="number" step="0.01" name="variant_price_eur[<?= e($vid) ?>]" value="<?= e(isset($variant['price_eur']) ? (string) $variant['price_eur'] : '') ?>" class="admin-input-sm"></td>
                    <td><input type="number" step="0.01" name="variant_price_usd[<?= e($vid) ?>]" value="<?= e(isset($variant['price_usd']) ? (string) $variant['price_usd'] : '') ?>" class="admin-input-sm"></td>
                    <td><?= e(isset($variant['price']) ? (string) $variant['price'] : '—') ?></td>
                    <td><a href="<?= e((string) ($variant['url'] ?? '#')) ?>" target="_blank" rel="noopener">↗</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </fieldset>

    <button type="submit" class="admin-btn">Зберегти</button>
</form>

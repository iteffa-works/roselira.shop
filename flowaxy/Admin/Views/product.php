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
        <?php $stockSummary = product_stock_summary($product); ?>
        <?php if ($stockSummary['total'] > 0 || ($product['inventory_note'] ?? '') !== ''): ?>
        <p class="admin-product-stock-summary">
            На складі: <strong><?= (int) $stockSummary['total'] ?> шт.</strong>
            · відтінків у наявності: <?= (int) $stockSummary['in_stock'] ?>
            <?php if (($product['inventory_note'] ?? '') !== ''): ?>
            · <?= e((string) $product['inventory_note']) ?>
            <?php endif; ?>
        </p>
        <?php endif; ?>
        <label class="admin-product-inventory-note">Примітка (салон тощо)
            <input type="text" name="inventory_note" value="<?= e((string) ($product['inventory_note'] ?? '')) ?>" placeholder="Салон: 28 блисків, 4 помади">
        </label>
        <table class="admin-table">
            <thead>
                <tr><th>№</th><th>Колір</th><th>Залишок</th><th>Active</th><th>EUR</th><th>USD</th><th>UAH</th><th>URL</th></tr>
            </thead>
            <tbody>
            <?php foreach ($product['variants'] ?? [] as $variant): ?>
                <?php
                    $vid = (string) ($variant['id'] ?? '');
                    $shade = variant_shade_code($vid);
                    $color = variant_color_name($vid);
                    $stock = array_key_exists('stock', $variant) ? (string) $variant['stock'] : '';
                ?>
                <tr>
                    <td>
                        <input type="hidden" name="variant_id[]" value="<?= e($vid) ?>">
                        <code><?= e($shade) ?></code>
                    </td>
                    <td>
                        <?= e($color !== '' ? ucwords($color) : $vid) ?>
                        <?php if (!empty($variant['swatch_image'])): ?>
                        <span class="admin-variant-swatch" style="background-image:url('<?= e(asset((string) $variant['swatch_image'])) ?>')"></span>
                        <?php elseif (!empty($variant['swatch'])): ?>
                        <span class="admin-variant-swatch" style="background-color:<?= e((string) $variant['swatch']) ?>"></span>
                        <?php endif; ?>
                    </td>
                    <td><input type="number" min="0" step="1" name="variant_stock[<?= e($vid) ?>]" value="<?= e($stock) ?>" class="admin-input-sm admin-input-stock"></td>
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

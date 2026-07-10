<h1>Каталог</h1>
<p class="admin-muted"><a href="<?= admin_url('categories') ?>">Категорії каталогу</a></p>

<div class="admin-card-list">
<?php foreach ($rows as $row): ?>
    <?php
        $slug = $row['slug'];
        $product = $row['product'];
        $price = $row['price'];
        $name = (string) ($product['i18n']['uk']['name'] ?? $slug);
        $isActive = ($product['active'] ?? false) === true;
        $variantCount = (int) count($product['variants'] ?? []);
        $stockSummary = $row['stockSummary'] ?? product_stock_summary($product);
    ?>
    <article class="admin-item-card">
        <header class="admin-item-card__head">
            <strong class="admin-item-card__title"><?= e($name) ?></strong>
            <span class="admin-badge <?= $isActive ? 'admin-badge--done' : 'admin-badge--cancelled' ?>"><?= $isActive ? 'Активний' : 'Вимкн.' ?></span>
        </header>
        <dl class="admin-item-card__meta">
            <div><dt>Ціна</dt><dd><?= $price !== null ? e(formatPrice((float) $price, 'UAH')) : '—' ?></dd></div>
            <div><dt>Група</dt><dd><?= e((string) ($product['group'] ?? '')) ?></dd></div>
            <div><dt>Варіантів</dt><dd><?= $variantCount ?><?php if ($stockSummary['total'] > 0): ?> · <?= (int) $stockSummary['total'] ?> шт.<?php endif; ?></dd></div>
            <div><dt>Slug</dt><dd><code class="admin-code"><?= e($slug) ?></code></dd></div>
        </dl>
        <footer class="admin-item-card__actions">
            <a href="<?= admin_url('product', ['slug' => $slug]) ?>" class="admin-btn admin-btn--sm admin-btn--block">Редагувати</a>
        </footer>
    </article>
<?php endforeach; ?>
</div>

<div class="admin-table-wrap admin-table-wrap--desktop">
<table class="admin-table">
    <thead>
        <tr>
            <th>Slug</th>
            <th>Назва</th>
            <th>Група</th>
            <th>Активний</th>
            <th>Ціна</th>
            <th>Варіантів</th>
            <th>Залишок</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <?php $slug = $row['slug']; $product = $row['product']; $price = $row['price']; ?>
        <tr>
            <td><code class="admin-code"><?= e($slug) ?></code></td>
            <td><?= e((string) ($product['i18n']['uk']['name'] ?? $slug)) ?></td>
            <td><?= e((string) ($product['group'] ?? '')) ?></td>
            <td><?= ($product['active'] ?? false) ? '✓' : '—' ?></td>
            <td><?= $price !== null ? e(formatPrice((float) $price, 'UAH')) : '—' ?></td>
            <td><?= (int) count($product['variants'] ?? []) ?></td>
            <td><?= ($row['stockSummary']['total'] ?? 0) > 0 ? (int) $row['stockSummary']['total'] . ' шт.' : '—' ?></td>
            <td><a href="<?= admin_url('product', ['slug' => $slug]) ?>">Редагувати</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

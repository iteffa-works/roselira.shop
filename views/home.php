<div class="container">
    <section class="home">
        <h1 class="home__title"><?= e(t('home_title')) ?></h1>
        <p class="home__subtitle"><?= e(t('home_subtitle')) ?></p>

        <?php foreach ($productGroups as $groupId => $groupProducts): ?>
        <section class="product-section">
            <h2 class="product-section__title"><?= e(t('group_' . $groupId)) ?></h2>
            <div class="product-grid">
                <?php foreach ($groupProducts as $product): ?>
                <a href="/<?= e($product['slug']) ?>" class="product-card">
                    <div class="product-card__image">
                        <img src="<?= e(asset($product['image'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy" onerror="this.src='<?= e(asset('assets/img/placeholder.svg')) ?>'">
                    </div>
                    <div class="product-card__body">
                        <span class="product-card__category"><?= e($product['category'] ?? '') ?></span>
                        <h3 class="product-card__name"><?= e($product['name']) ?></h3>
                        <?php if (!empty($product['price'])): ?>
                        <div class="product-card__price">
                            <span class="price"><?= e(formatPrice((float) $product['price'], (string) ($product['price_currency'] ?? 'USD'))) ?></span>
                            <?php if (!empty($product['price_old'])): ?>
                            <span class="price price--old"><?= e(formatPrice((float) $product['price_old'], (string) ($product['price_currency'] ?? 'USD'))) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php elseif (!empty($product['_hasRating'])): ?>
                        <div class="product-card__rating">
                            <?= renderStars((float) ($product['rating'] ?? 0)) ?>
                            <span class="rating-value"><?= e(number_format((float) ($product['rating'] ?? 0), 1)) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </section>
</div>

<?php
$defaultImages = $defaultVariant['images'] ?? [($product['image'] ?? 'assets/img/placeholder.svg')];
$hasMultipleVariants = count($product['variants'] ?? []) > 1;
$placeholder = asset('assets/img/placeholder.svg');
$productJson = json_encode([
    'slug' => $product['slug'] ?? '',
    'default_variant' => $product['default_variant'] ?? '',
    'price' => $product['price'] ?? null,
    'price_old' => $product['price_old'] ?? null,
    'price_currency' => $product['price_currency'] ?? 'UAH',
    'variants' => array_map(static function (array $variant) use ($product): array {
        $videos = [];
        foreach ($variant['videos'] ?? [] as $video) {
            if (!is_array($video) || empty($video['src'])) {
                continue;
            }
            $item = ['src' => asset((string) $video['src'])];
            if (!empty($video['poster'])) {
                $item['poster'] = asset((string) $video['poster']);
            }
            $videos[] = $item;
        }

        return [
            'id' => $variant['id'] ?? '',
            'name' => $variant['name'] ?? '',
            'swatch' => $variant['swatch'] ?? '#d4d4d4',
            'swatch_image' => !empty($variant['swatch_image']) ? asset((string) $variant['swatch_image']) : null,
            'images' => array_map(static fn(string $image): string => asset($image), $variant['images'] ?? []),
            'videos' => $videos,
            'price' => $variant['price'] ?? ($product['price'] ?? null),
            'price_old' => $variant['price_old'] ?? ($product['price_old'] ?? null),
        ];
    }, $product['variants'] ?? []),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<div class="container">
    <article
        class="landing"
        data-product="<?= e($productJson) ?>"
        data-placeholder="<?= e($placeholder) ?>"
    >
        <div class="landing__hero">
            <div class="landing__gallery">
                <div class="gallery__main" data-gallery-main>
                    <img
                        class="gallery__main-image"
                        src="<?= e(asset($defaultImages[0])) ?>"
                        alt="<?= e($product['name']) ?>"
                        onerror="this.src='<?= e($placeholder) ?>'"
                    >
                    <video
                        class="gallery__main-video"
                        playsinline
                        controls
                        preload="none"
                        hidden
                    ></video>
                </div>
                <?php if (count($defaultImages) > 1): ?>
                <div class="gallery__thumbs">
                    <?php foreach ($defaultImages as $index => $image): ?>
                    <button
                        type="button"
                        class="gallery__thumb<?= $index === 0 ? ' is-active' : '' ?>"
                        data-index="<?= (int) $index ?>"
                    >
                        <img src="<?= e(asset($image)) ?>" alt="" onerror="this.src='<?= e($placeholder) ?>'">
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="landing__info">
                <span class="landing__brand"><?= e($product['brand'] ?? 'Roselira') ?></span>
                <span class="landing__category"><?= e($product['category'] ?? '') ?></span>
                <h1 class="landing__title"><?= e($product['name']) ?></h1>

                <?php if (!empty($hasRating)): ?>
                <div class="landing__rating">
                    <?= renderStars((float) ($product['rating'] ?? 0)) ?>
                    <span class="rating-value"><?= e(number_format((float) ($product['rating'] ?? 0), 1)) ?>/5</span>
                    <span class="rating-count">(<?= e((string) ($product['reviews_count'] ?? 0)) ?> <?= e(t('reviews')) ?>)</span>
                </div>
                <?php endif; ?>

                <?php if ($hasMultipleVariants): ?>
                <?php $variantCount = count($product['variants']); ?>
                <div class="variant-picker">
                    <div class="variant-picker__head">
                        <p class="variant-picker__selected" data-variant-name><?= e($defaultVariant['name'] ?? '') ?></p>
                        <?php if ($variantCount > 1): ?>
                        <button
                            type="button"
                            class="variant-picker__toggle"
                            data-variant-toggle
                            aria-expanded="false"
                            data-label-expand="<?= e(t('variant_all', ['count' => (string) $variantCount])) ?>"
                            data-label-collapse="<?= e(t('variant_collapse')) ?>"
                        ><?= e(t('variant_all', ['count' => (string) $variantCount])) ?></button>
                        <?php endif; ?>
                    </div>
                    <div class="variant-picker__track">
                        <div
                            class="variant-picker__swatches is-collapsed"
                            data-variant-swatches
                            role="listbox"
                            aria-label="<?= e(t('variant_label')) ?>"
                        >
                        <?php foreach ($product['variants'] as $variant): ?>
                        <button
                            type="button"
                            class="variant-swatch<?= ($variant['id'] ?? '') === ($product['default_variant'] ?? '') ? ' is-active' : '' ?>"
                            role="option"
                            data-variant-id="<?= e($variant['id'] ?? '') ?>"
                            aria-selected="<?= ($variant['id'] ?? '') === ($product['default_variant'] ?? '') ? 'true' : 'false' ?>"
                            title="<?= e($variant['name'] ?? '') ?>"
                        >
                            <span class="variant-swatch__color"<?php if (!empty($variant['swatch_image'])): ?> style="background-image: url('<?= e(asset((string) $variant['swatch_image'])) ?>'); background-size: cover; background-position: center"<?php else: ?> style="background-color: <?= e($variant['swatch'] ?? '#d4d4d4') ?>"<?php endif; ?>></span>
                            <span class="variant-swatch__name"><?= e($variant['name'] ?? '') ?></span>
                        </button>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="landing__price<?= empty($product['price']) ? ' is-hidden' : '' ?>" data-price-block>
                    <span class="price" data-price-current><?= !empty($product['price']) ? e(formatPrice((float) $product['price'], (string) ($product['price_currency'] ?? 'USD'))) : '' ?></span>
                    <span class="price price--old<?= empty($product['price_old']) ? ' is-hidden' : '' ?>" data-price-old><?= !empty($product['price_old']) ? e(formatPrice((float) $product['price_old'], (string) ($product['price_currency'] ?? 'USD'))) : '' ?></span>
                </div>

                <p class="landing__desc"><?= e($product['short_desc'] ?? '') ?></p>

                <?php if (!empty($product['benefits'])): ?>
                <ul class="landing__benefits">
                    <?php foreach ($product['benefits'] as $benefit): ?>
                    <li><?= e($benefit) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <?php if (!empty($showOrderForm)): ?>
                <form class="order-form" data-order-form action="/order" method="post" novalidate data-error-network="<?= e(t('order_error_server')) ?>" data-error-captcha="<?= e(t('order_error_captcha')) ?>">
                    <h2 class="order-form__title"><?= e(t('order_title')) ?></h2>
                    <input type="hidden" name="product_slug" value="<?= e($product['slug'] ?? '') ?>">
                    <input type="hidden" name="variant_id" value="<?= e($product['default_variant'] ?? '') ?>" data-variant-input>
                    <div class="order-form__hp" aria-hidden="true">
                        <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <label class="order-form__field">
                        <span><?= e(t('order_name')) ?></span>
                        <input type="text" name="name" required autocomplete="name" placeholder="<?= e(t('order_name_placeholder')) ?>">
                    </label>

                    <label class="order-form__field">
                        <span><?= e(t('order_phone')) ?></span>
                        <input type="tel" name="phone" required autocomplete="tel" placeholder="<?= e(t('order_phone_placeholder')) ?>">
                    </label>

                    <label class="order-form__field">
                        <span><?= e(t('order_comment')) ?></span>
                        <textarea name="comment" rows="3" placeholder="<?= e(t('order_comment_placeholder')) ?>"></textarea>
                    </label>

                    <?php if (recaptcha_enabled()): ?>
                    <?php
                    $recaptchaClass = 'recaptcha-widget--order';
                    $recaptchaTheme = 'auto';
                    require __DIR__ . '/partials/recaptcha.php';
                    ?>
                    <?php endif; ?>

                    <button type="submit" class="cta-button order-form__submit"><?= e(t('order_submit')) ?></button>
                    <p class="order-form__message" data-order-message hidden></p>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php
        $sectionKeys = ['description', 'results', 'tips', 'pack', 'ingredients', 'disposal'];
        $sections = $product['sections'] ?? [];
        $hasSections = false;
        foreach ($sectionKeys as $sectionKey) {
            if (!empty($sections[$sectionKey])) {
                $hasSections = true;
                break;
            }
        }
        ?>
        <?php if ($hasSections): ?>
        <?php
            $activeSections = [];
            foreach ($sectionKeys as $sectionKey) {
                if (!empty($sections[$sectionKey])) {
                    $activeSections[] = $sectionKey;
                }
            }
            $splitAt = (int) ceil(count($activeSections) / 2);
            $accordionColumns = array_values(array_filter([
                array_slice($activeSections, 0, $splitAt),
                array_slice($activeSections, $splitAt),
            ], static fn(array $column): bool => $column !== []));
        ?>
        <section class="landing__details">
            <div class="product-accordion" data-product-accordion>
                <?php foreach ($accordionColumns as $columnKeys): ?>
                <div class="product-accordion__col">
                    <?php foreach ($columnKeys as $sectionKey): ?>
                    <details class="product-accordion__item" data-section="<?= e($sectionKey) ?>"<?= $sectionKey === 'description' ? ' open' : '' ?>>
                        <summary class="product-accordion__summary">
                            <span class="product-accordion__icon" aria-hidden="true"></span>
                            <span class="product-accordion__label"><?= e(t('section_' . $sectionKey)) ?></span>
                            <span class="product-accordion__chevron" aria-hidden="true"></span>
                        </summary>
                        <div class="product-accordion__body"><?= nl2br(e((string) $sections[$sectionKey])) ?></div>
                    </details>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php elseif (!empty($product['description'])): ?>
        <section class="landing__description">
            <h2 class="landing__description-title"><?= e(t('description_title')) ?></h2>
            <div class="landing__description-text"><?= nl2br(e($product['description'])) ?></div>
        </section>
        <?php endif; ?>
    </article>
</div>

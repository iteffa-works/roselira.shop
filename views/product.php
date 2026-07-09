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
            'active' => ($variant['active'] ?? true) !== false,
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
                <div class="gallery">
                    <div class="gallery__main" data-gallery-main tabindex="0">
                        <button type="button" class="gallery__nav gallery__nav--prev" data-gallery-prev aria-label="Previous" hidden>
                            <span aria-hidden="true"></span>
                        </button>
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
                        <button type="button" class="gallery__nav gallery__nav--next" data-gallery-next aria-label="Next" hidden>
                            <span aria-hidden="true"></span>
                        </button>
                        <span class="gallery__counter" data-gallery-counter hidden></span>
                    </div>
                    <div class="gallery__thumbs-track">
                        <div
                            class="gallery__thumbs<?= count($defaultImages) <= 1 ? ' is-hidden' : '' ?>"
                            data-gallery-thumbs
                        >
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
                    </div>
                </div>
            </div>

            <div class="landing__info">
                <div class="landing__summary">
                    <div class="landing__header">
                        <div class="landing__meta-row">
                            <div class="landing__meta">
                                <span class="landing__brand"><?= e($product['brand'] ?? 'Roselira') ?></span>
                                <?php if (!empty($product['category'])): ?>
                                <span class="landing__category"><?= e($product['category']) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($hasRating)): ?>
                            <div class="landing__rating">
                                <?= renderRatingWidget(
                                    (string) ($productSlug ?? $product['slug'] ?? ''),
                                    (float) ($displayRating ?? $product['rating'] ?? 0),
                                    (int) ($displayReviewCount ?? $product['reviews_count'] ?? 0),
                                    isset($userRatingVote) ? (int) $userRatingVote : null,
                                ) ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="landing__headline">
                            <h1 class="landing__title"><?= e($product['name']) ?></h1>
                            <?php if (!$hasMultipleVariants): ?>
                            <div class="landing__price<?= empty($product['price']) ? ' is-hidden' : '' ?>" data-price-block>
                                <span class="price" data-price-current><?= !empty($product['price']) ? e(formatPrice((float) $product['price'], (string) ($product['price_currency'] ?? 'USD'))) : '' ?></span>
                                <span class="price price--old<?= empty($product['price_old']) ? ' is-hidden' : '' ?>" data-price-old><?= !empty($product['price_old']) ? e(formatPrice((float) $product['price_old'], (string) ($product['price_currency'] ?? 'USD'))) : '' ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php if ($hasMultipleVariants): ?>
                <?php $variantCount = count($product['variants']); ?>
                <div class="variant-picker variant-picker--embedded">
                    <div class="variant-picker__head">
                        <p class="variant-picker__selected" data-variant-name><?= e($defaultVariant['name'] ?? '') ?></p>
                        <div class="variant-picker__aside">
                            <div class="landing__price variant-picker__price<?= empty($product['price']) ? ' is-hidden' : '' ?>" data-price-block>
                                <span class="price" data-price-current><?= !empty($product['price']) ? e(formatPrice((float) $product['price'], (string) ($product['price_currency'] ?? 'USD'))) : '' ?></span>
                                <span class="price price--old<?= empty($product['price_old']) ? ' is-hidden' : '' ?>" data-price-old><?= !empty($product['price_old']) ? e(formatPrice((float) $product['price_old'], (string) ($product['price_currency'] ?? 'USD'))) : '' ?></span>
                            </div>
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
                    </div>
                    <div class="variant-picker__track">
                        <div
                            class="variant-picker__swatches is-collapsed"
                            data-variant-swatches
                            role="listbox"
                            aria-label="<?= e(t('variant_label')) ?>"
                        >
                        <?php foreach ($product['variants'] as $variant): ?>
                        <?php $variantAvailable = ($variant['active'] ?? true) !== false; ?>
                        <button
                            type="button"
                            class="variant-swatch<?= ($variant['id'] ?? '') === ($product['default_variant'] ?? '') ? ' is-active' : '' ?><?= !$variantAvailable ? ' is-unavailable' : '' ?>"
                            role="option"
                            data-variant-id="<?= e($variant['id'] ?? '') ?>"
                            aria-selected="<?= ($variant['id'] ?? '') === ($product['default_variant'] ?? '') ? 'true' : 'false' ?>"
                            title="<?= e($variant['name'] ?? '') ?><?= !$variantAvailable ? ' — ' . e(t('variant_unavailable')) : '' ?>"
                        >
                            <span class="variant-swatch__color"<?php if (!empty($variant['swatch_image'])): ?> style="background-image: url('<?= e(asset((string) $variant['swatch_image'])) ?>'); background-size: cover; background-position: center"<?php else: ?> style="background-color: <?= e($variant['swatch'] ?? '#d4d4d4') ?>"<?php endif; ?>></span>
                            <span class="variant-swatch__name"><?= e($variant['name'] ?? '') ?><?php if (!$variantAvailable): ?><span class="variant-swatch__status"><?= e(t('variant_unavailable')) ?></span><?php endif; ?></span>
                        </button>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                </div>

                <?php if (!empty($product['short_desc']) || !empty($product['benefits'])): ?>
                <div class="landing__pitch">
                <?php if (!empty($product['short_desc'])): ?>
                <p class="landing__desc"><?= e($product['short_desc']) ?></p>
                <?php endif; ?>

                <?php if (!empty($product['benefits'])): ?>
                <ul class="landing__benefits">
                    <?php foreach ($product['benefits'] as $benefit): ?>
                    <li><?= e($benefit) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                </div>
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
        ?>
        <section class="landing__details">
            <div class="product-tabs" data-product-tabs>
                <div class="product-tabs__nav" role="tablist" aria-label="<?= e(t('description_title')) ?>">
                    <?php foreach ($activeSections as $index => $sectionKey): ?>
                    <button
                        type="button"
                        class="product-tabs__tab<?= $index === 0 ? ' is-active' : '' ?>"
                        role="tab"
                        id="product-tab-<?= e($sectionKey) ?>"
                        aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                        aria-controls="product-panel-<?= e($sectionKey) ?>"
                        data-section="<?= e($sectionKey) ?>"
                    >
                        <span class="product-tabs__icon" aria-hidden="true"></span>
                        <span class="product-tabs__label"><?= e(t('section_' . $sectionKey)) ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
                <div class="product-tabs__panels">
                    <?php foreach ($activeSections as $index => $sectionKey): ?>
                    <div
                        class="product-tabs__panel<?= $index === 0 ? ' is-active' : '' ?>"
                        role="tabpanel"
                        id="product-panel-<?= e($sectionKey) ?>"
                        aria-labelledby="product-tab-<?= e($sectionKey) ?>"
                        data-section="<?= e($sectionKey) ?>"
                        <?= $index !== 0 ? ' hidden' : '' ?>
                    >
                        <div class="product-tabs__content"><?= nl2br(e((string) $sections[$sectionKey])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
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

<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Repositories\Contracts\CatalogRepositoryInterface;
use Flowaxy\Repositories\Contracts\SettingsRepositoryInterface;

final class CatalogService
{
    public const SETTING_SHIPPING = 'merchant_shipping_json';
    public const SETTING_RETURN = 'merchant_return_json';

    private const GPC_TAXONOMY = 'https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt';

    private ?array $catalogCache = null;

    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly LocaleService $locale,
        private readonly SettingsRepositoryInterface $settings,
    ) {
    }

    public function clearCache(): void
    {
        $this->catalogCache = null;
    }

    public function loadCatalog(): array
    {
        if ($this->catalogCache !== null) {
            return $this->catalogCache;
        }

        $this->catalogCache = $this->catalog->load();

        return $this->catalogCache;
    }

    /** @return array<string, array<string, mixed>> */
    public function loadProducts(): array
    {
        $products = $this->loadCatalog()['products'] ?? [];
        $normalized = [];

        foreach ($products as $slug => $product) {
            if (!is_array($product)) {
                continue;
            }

            $product['slug'] = (string) $slug;
            $normalized[(string) $slug] = $product;
        }

        return $normalized;
    }

    public function saveCatalog(array $catalog): bool
    {
        $this->clearCache();

        return $this->catalog->save($catalog);
    }

    /** @return array<string, array<string, mixed>> */
    public function loadGroups(): array
    {
        return $this->loadCatalog()['groups'] ?? [];
    }

    /** @return array<string, array<string, mixed>> */
    public function loadCategories(): array
    {
        $categories = $this->loadCatalog()['categories'] ?? [];
        if (!is_array($categories)) {
            return [];
        }

        uasort($categories, static function (array $a, array $b): int {
            return ((int) ($a['order'] ?? 999)) <=> ((int) ($b['order'] ?? 999));
        });

        return $categories;
    }

    public function findCategory(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }

        $category = $this->loadCategories()[$id] ?? null;

        return is_array($category) ? $category : null;
    }

    /**
     * @param array<string, array<string, mixed>> $categories
     */
    public function saveCategories(array $categories): bool
    {
        $catalog = $this->loadCatalog();
        $catalog['categories'] = $categories;

        return $this->saveCatalog($catalog);
    }

    /** @return array{ok: bool, message: string, id?: string} */
    public function upsertCategory(string $id, array $input, ?string $originalId = null): array
    {
        $id = $this->normalizeCategoryId($id);
        if ($id === '') {
            return ['ok' => false, 'message' => 'Вкажіть ID категорії (латиниця, цифри, - _).'];
        }

        $originalId = $originalId !== null ? $this->normalizeCategoryId($originalId) : '';
        $isCreate = $originalId === '';
        $catalog = $this->loadCatalog();
        /** @var array<string, array<string, mixed>> $categories */
        $categories = is_array($catalog['categories'] ?? null) ? $catalog['categories'] : [];

        if ($isCreate && isset($categories[$id])) {
            return ['ok' => false, 'message' => 'Категорія з таким ID вже існує.'];
        }

        if (!$isCreate) {
            if (!isset($categories[$originalId])) {
                return ['ok' => false, 'message' => 'Категорію не знайдено.'];
            }
            if ($originalId !== $id) {
                if (isset($categories[$id])) {
                    return ['ok' => false, 'message' => 'Категорія з таким ID вже існує.'];
                }
                unset($categories[$originalId]);
                foreach ($catalog['products'] ?? [] as $slug => $product) {
                    if (!is_array($product)) {
                        continue;
                    }
                    if (($product['category_id'] ?? '') === $originalId) {
                        $catalog['products'][$slug]['category_id'] = $id;
                    }
                }
            }
        }

        $labels = [];
        foreach (['uk', 'ru', 'en'] as $loc) {
            $labels[$loc] = trim((string) ($input['labels'][$loc] ?? ''));
        }
        if ($labels['uk'] === '' && $labels['ru'] === '' && $labels['en'] === '') {
            return ['ok' => false, 'message' => 'Вкажіть хоча б одну назву категорії.'];
        }

        $gpc = preg_replace('/\D+/', '', trim((string) ($input['google_product_category'] ?? ''))) ?? '';
        $order = (int) ($input['order'] ?? ($categories[$id]['order'] ?? ($categories[$originalId]['order'] ?? 999)));

        $categories[$id] = [
            'order' => $order > 0 ? $order : 999,
            'google_product_category' => $gpc,
            'labels' => $labels,
        ];
        $catalog['categories'] = $categories;

        if (!$this->saveCatalog($catalog)) {
            return ['ok' => false, 'message' => 'Не вдалося зберегти категорію.'];
        }

        return ['ok' => true, 'message' => 'Категорію збережено.', 'id' => $id];
    }

    /** @return array{ok: bool, message: string} */
    public function deleteCategory(string $id): array
    {
        $id = $this->normalizeCategoryId($id);
        $catalog = $this->loadCatalog();
        /** @var array<string, array<string, mixed>> $categories */
        $categories = is_array($catalog['categories'] ?? null) ? $catalog['categories'] : [];
        if ($id === '' || !isset($categories[$id])) {
            return ['ok' => false, 'message' => 'Категорію не знайдено.'];
        }

        $inUse = 0;
        foreach ($catalog['products'] ?? [] as $product) {
            if (is_array($product) && ($product['category_id'] ?? '') === $id) {
                $inUse++;
            }
        }
        if ($inUse > 0) {
            return [
                'ok' => false,
                'message' => "Категорію використовують {$inUse} товар(ів). Спочатку змініть категорію в товарах.",
            ];
        }

        unset($categories[$id]);
        $catalog['categories'] = $categories;
        if (!$this->saveCatalog($catalog)) {
            return ['ok' => false, 'message' => 'Не вдалося видалити.'];
        }

        return ['ok' => true, 'message' => 'Категорію видалено.'];
    }

    public function ensureCategoriesBootstrapped(): void
    {
        $catalog = $this->loadCatalog();
        if (is_array($catalog['categories'] ?? null) && $catalog['categories'] !== []) {
            return;
        }

        $byKey = [];
        foreach ($catalog['products'] ?? [] as $product) {
            if (!is_array($product)) {
                continue;
            }
            $en = trim((string) ($product['i18n']['en']['category'] ?? ''));
            $uk = trim((string) ($product['i18n']['uk']['category'] ?? ''));
            $ru = trim((string) ($product['i18n']['ru']['category'] ?? ''));
            $gpc = trim((string) ($product['google_product_category'] ?? ''));
            $idSource = $en !== '' ? $en : ($uk !== '' ? $uk : $ru);
            if ($idSource === '') {
                continue;
            }

            $id = $this->normalizeCategoryId($idSource);
            if ($id === '') {
                continue;
            }

            if (!isset($byKey[$id])) {
                $byKey[$id] = [
                    'order' => count($byKey) + 1,
                    'google_product_category' => $gpc,
                    'labels' => [
                        'uk' => $uk,
                        'ru' => $ru,
                        'en' => $en,
                    ],
                ];
            } elseif ($gpc !== '' && ($byKey[$id]['google_product_category'] ?? '') === '') {
                $byKey[$id]['google_product_category'] = $gpc;
            }
        }

        if ($byKey === []) {
            return;
        }

        $catalog['categories'] = $byKey;
        foreach ($catalog['products'] ?? [] as $slug => $product) {
            if (!is_array($product)) {
                continue;
            }
            $en = trim((string) ($product['i18n']['en']['category'] ?? ''));
            $uk = trim((string) ($product['i18n']['uk']['category'] ?? ''));
            $idSource = $en !== '' ? $en : $uk;
            $id = $this->normalizeCategoryId($idSource);
            if ($id !== '' && isset($byKey[$id])) {
                $catalog['products'][$slug]['category_id'] = $id;
            }
        }

        $this->saveCatalog($catalog);
    }

    public function normalizeCategoryId(string $id): string
    {
        $id = strtolower(trim($id));
        $id = preg_replace('/[^a-z0-9_-]+/', '-', $id) ?? '';

        return trim($id, '-_');
    }

    public function getExchangeRatesMeta(): array
    {
        $rates = $this->loadCatalog()['meta']['exchange_rates'] ?? [];

        return [
            'source' => (string) ($rates['source'] ?? 'NBU'),
            'date' => (string) ($rates['date'] ?? ''),
            'EUR' => (float) ($rates['EUR'] ?? 0),
            'USD' => (float) ($rates['USD'] ?? 0),
        ];
    }

    public function localizeProduct(array $product, ?string $locale = null): array
    {
        $locale = $locale ?? $this->locale->current();
        $translations = $product['i18n'] ?? [];
        $fallback = $translations[$this->locale->fallback()] ?? [];

        foreach (['name', 'category', 'short_desc', 'benefits', 'description'] as $field) {
            if (!empty($translations[$locale][$field])) {
                $product[$field] = $translations[$locale][$field];
            } elseif (!empty($fallback[$field])) {
                $product[$field] = $fallback[$field];
            }
        }

        $categoryId = trim((string) ($product['category_id'] ?? ''));
        if ($categoryId !== '') {
            $category = $this->findCategory($categoryId);
            if ($category !== null) {
                $labels = is_array($category['labels'] ?? null) ? $category['labels'] : [];
                $label = trim((string) ($labels[$locale] ?? ''));
                if ($label === '') {
                    $label = trim((string) ($labels[$this->locale->fallback()] ?? ''));
                }
                if ($label === '') {
                    $label = trim((string) ($labels['en'] ?? $labels['uk'] ?? $labels['ru'] ?? ''));
                }
                if ($label !== '') {
                    $product['category'] = $label;
                }
            }
        }

        if (!empty($translations[$locale]['sections']) && is_array($translations[$locale]['sections'])) {
            $product['sections'] = $translations[$locale]['sections'];
        } elseif (!empty($fallback['sections']) && is_array($fallback['sections'])) {
            $product['sections'] = $fallback['sections'];
        }

        if (!empty($product['variants'])) {
            $variantNames = $translations[$locale]['variants'] ?? [];
            $fallbackNames = $fallback['variants'] ?? [];

            foreach ($product['variants'] as $index => $variant) {
                $variantId = (string) ($variant['id'] ?? '');
                $product['variants'][$index]['name'] = $variantNames[$variantId]
                    ?? $fallbackNames[$variantId]
                    ?? $variantId;
            }
        }

        return $this->normalizeProductVariants($product);
    }

    public function findProduct(string $slug, ?string $locale = null): ?array
    {
        $products = $this->loadProducts();

        if (!isset($products[$slug])) {
            return null;
        }

        $product = $products[$slug];

        if (!($product['active'] ?? false)) {
            return null;
        }

        return $this->localizeProduct($product, $locale);
    }

    /** @return list<array<string, mixed>> */
    public function getActiveProducts(?string $locale = null): array
    {
        return array_values(array_map(
            fn(array $product): array => $this->localizeProduct($product, $locale),
            array_filter(
                $this->loadProducts(),
                static fn(array $product): bool => ($product['active'] ?? false) === true,
            ),
        ));
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function getGroupedActiveProducts(?string $locale = null): array
    {
        $grouped = [];
        $groupConfig = $this->loadGroups();

        foreach ($this->getActiveProducts($locale) as $product) {
            $groupId = (string) ($product['group'] ?? 'other');
            $grouped[$groupId][] = $product;
        }

        uksort($grouped, static function (string $a, string $b) use ($groupConfig): int {
            $orderA = (int) ($groupConfig[$a]['order'] ?? 999);
            $orderB = (int) ($groupConfig[$b]['order'] ?? 999);

            return $orderA <=> $orderB;
        });

        return $grouped;
    }

    public function getDefaultVariant(array $product): array
    {
        $defaultId = (string) ($product['default_variant'] ?? '');
        $variant = $this->findVariantById($product, $defaultId);

        return $variant ?? ($product['variants'][0] ?? []);
    }

    public function findVariant(array $product, string $variantId): ?array
    {
        return $this->findVariantById($product, $variantId);
    }

    /** @return array<string, mixed>|null */
    private function findVariantById(array $product, string $variantId): ?array
    {
        foreach ($product['variants'] ?? [] as $variant) {
            if (($variant['id'] ?? '') === $variantId) {
                return $variant;
            }
        }

        return null;
    }

    /** @return array{slug: string, product: array<string, mixed>}|null */
    public function resolveRawProduct(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $products = $this->loadCatalog()['products'] ?? [];
        if (!isset($products[$slug]) || !is_array($products[$slug])) {
            return null;
        }

        return ['slug' => $slug, 'product' => $products[$slug]];
    }

    public function productUsesOrderForm(array $product): bool
    {
        return ($product['order_mode'] ?? 'form') === 'form';
    }

public function productHasRating(array $product): bool
    {
        return ((float) ($product['rating'] ?? 0)) > 0
            || ((int) ($product['reviews_count'] ?? 0)) > 0;
    }

    /** @return array<string, mixed> */
    public function buildProductStructuredData(
        array $product,
        string $slug,
        array $defaultVariant,
        ?float $price,
        string $currency,
    ): array {
        $imagePath = (string) ($defaultVariant['images'][0] ?? $product['image'] ?? '');
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => (string) ($product['name'] ?? ''),
            'description' => strip_tags((string) ($product['short_desc'] ?? '')),
            'image' => absolute_url($imagePath),
            'url' => absolute_url('/' . rawurlencode($slug)),
            'sku' => $slug,
            'brand' => [
                '@type' => 'Brand',
                'name' => (string) ($product['brand'] ?? 'Roselira'),
            ],
        ];

        $gpc = $this->resolveGoogleProductCategory($product);
        if ($gpc !== '') {
            $jsonLd['category'] = [
                '@type' => 'CategoryCode',
                'inCodeSet' => self::GPC_TAXONOMY,
                'codeValue' => $gpc,
            ];
        }

        if ($price !== null) {
            $defaultVariant = $this->getDefaultVariant($product);
            $inStock = $defaultVariant !== [] && variant_has_stock($defaultVariant);
            $jsonLd['offers'] = [
                '@type' => 'Offer',
                'url' => absolute_url('/' . rawurlencode($slug)),
                'priceCurrency' => $currency,
                'price' => number_format($price, 2, '.', ''),
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'shippingDetails' => $this->offerShippingDetails($currency),
                'hasMerchantReturnPolicy' => $this->offerReturnPolicy(),
            ];
        }

        $rating = (float) ($product['rating'] ?? 0);
        $reviewCount = (int) ($product['reviews_count'] ?? 0);
        if ($rating > 0 && $reviewCount > 0) {
            $jsonLd['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($rating, 1),
                'reviewCount' => $reviewCount,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        $reviews = $this->structuredReviews($product);
        if ($reviews !== []) {
            $jsonLd['review'] = count($reviews) === 1 ? $reviews[0] : $reviews;
        }

        return $jsonLd;
    }

    private function resolveGoogleProductCategory(array $product): string
    {
        $categoryId = trim((string) ($product['category_id'] ?? ''));
        if ($categoryId !== '') {
            $category = $this->findCategory($categoryId);
            if ($category !== null) {
                $gpc = trim((string) ($category['google_product_category'] ?? ''));
                if ($gpc !== '') {
                    return $gpc;
                }
            }
        }

        return trim((string) ($product['google_product_category'] ?? ''));
    }

    /** @return array<string, mixed> */
    private function offerShippingDetails(string $offerCurrency): array
    {
        $cfg = $this->merchantShippingConfig();
        $currency = (string) ($cfg['currency'] ?? $offerCurrency);
        if ($currency === '') {
            $currency = $offerCurrency !== '' ? $offerCurrency : 'UAH';
        }

        return [
            '@type' => 'OfferShippingDetails',
            'shippingRate' => [
                '@type' => 'MonetaryAmount',
                'value' => number_format((float) ($cfg['rate'] ?? 100), 2, '.', ''),
                'currency' => $currency,
            ],
            'shippingDestination' => [
                '@type' => 'DefinedRegion',
                'addressCountry' => (string) ($cfg['country'] ?? 'UA'),
            ],
            'deliveryTime' => [
                '@type' => 'ShippingDeliveryTime',
                'handlingTime' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => (int) ($cfg['handling_min'] ?? 1),
                    'maxValue' => (int) ($cfg['handling_max'] ?? 3),
                    'unitCode' => 'DAY',
                ],
                'transitTime' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => (int) ($cfg['transit_min'] ?? 1),
                    'maxValue' => (int) ($cfg['transit_max'] ?? 3),
                    'unitCode' => 'DAY',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function offerReturnPolicy(): array
    {
        $cfg = $this->merchantReturnConfig();
        $days = (int) ($cfg['days'] ?? 14);
        $policyUrl = trim((string) ($cfg['url'] ?? '/delivery'));
        if ($policyUrl === '') {
            $policyUrl = '/delivery';
        }

        return [
            '@type' => 'MerchantReturnPolicy',
            'applicableCountry' => (string) ($cfg['country'] ?? 'UA'),
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays' => $days > 0 ? $days : 14,
            'returnMethod' => 'https://schema.org/ReturnByMail',
            'returnFees' => 'https://schema.org/ReturnShippingFees',
            'url' => absolute_url($policyUrl),
        ];
    }

    /** @return array<string, mixed> */
    private function merchantShippingConfig(): array
    {
        return $this->decodeMerchantSetting(self::SETTING_SHIPPING, [
            'rate' => 100,
            'currency' => 'UAH',
            'country' => 'UA',
            'handling_min' => 1,
            'handling_max' => 3,
            'transit_min' => 1,
            'transit_max' => 3,
        ]);
    }

    /** @return array<string, mixed> */
    private function merchantReturnConfig(): array
    {
        return $this->decodeMerchantSetting(self::SETTING_RETURN, [
            'days' => 14,
            'country' => 'UA',
            'url' => '/delivery',
        ]);
    }

    /**
     * @param array<string, mixed> $defaults
     *
     * @return array<string, mixed>
     */
    private function decodeMerchantSetting(string $key, array $defaults): array
    {
        $raw = $this->settings->get($key);
        if ($raw === null || $raw === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return array_merge($defaults, $decoded);
    }

    /** @return list<array<string, mixed>> */
    private function structuredReviews(array $product): array
    {
        $rawReviews = $product['reviews'] ?? [];
        if (!is_array($rawReviews) || $rawReviews === []) {
            return [];
        }

        $structured = [];
        foreach ($rawReviews as $review) {
            if (!is_array($review)) {
                continue;
            }

            $ratingValue = (float) ($review['rating'] ?? $review['ratingValue'] ?? 0);
            $body = trim((string) ($review['body'] ?? $review['reviewBody'] ?? ''));
            $author = trim((string) ($review['author'] ?? ''));
            if ($ratingValue <= 0 || $body === '' || $author === '') {
                continue;
            }

            $item = [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name' => $author,
                ],
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => round($ratingValue, 1),
                    'bestRating' => 5,
                    'worstRating' => 1,
                ],
                'reviewBody' => $body,
            ];

            $datePublished = trim((string) ($review['date'] ?? $review['datePublished'] ?? ''));
            if ($datePublished !== '') {
                $item['datePublished'] = $datePublished;
            }

            $structured[] = $item;
        }

        return $structured;
    }

    /** @return list<array{id: string, title: string, description: string, link: string, image: string, price: string, availability: string, brand: string}> */
    public function getFeedItems(?string $locale = null): array
    {
        $items = [];

        foreach ($this->getActiveProducts($locale ?? 'uk') as $product) {
            $slug = (string) ($product['slug'] ?? '');
            if ($slug === '') {
                continue;
            }

            $variant = $this->getDefaultVariant($product);
            $imagePath = $variant['images'][0] ?? ($product['image'] ?? 'assets/img/placeholder.svg');
            $price = $variant['price'] ?? $product['price'] ?? null;
            $currency = (string) ($variant['price_currency'] ?? $product['price_currency'] ?? 'UAH');

            if ($price === null) {
                continue;
            }

            $description = strip_tags((string) ($product['short_desc'] ?? $product['name'] ?? ''));
            if (strlen($description) > 5000) {
                $description = mb_substr($description, 0, 4997) . '...';
            }

            $items[] = [
                'id' => $slug,
                'title' => (string) ($product['name'] ?? $slug),
                'description' => $description,
                'link' => absolute_url('/' . rawurlencode($slug)),
                'image' => absolute_url((string) $imagePath),
                'price' => number_format((float) $price, 2, '.', '') . ' ' . $currency,
                'availability' => variant_has_stock($variant) ? 'in stock' : 'out of stock',
                'brand' => (string) ($product['brand'] ?? 'Roselira'),
            ];
        }

        return $items;
    }

    private function normalizeProductVariants(array $product): array
    {
        if (!empty($product['variants'])) {
            $variants = array_values($product['variants']);
            $defaultId = (string) ($product['default_variant'] ?? '');

            foreach ($variants as $index => $variant) {
                if (empty($variant['images'])) {
                    $variants[$index]['images'] = [$product['image'] ?? 'assets/img/placeholder.svg'];
                }

                if (empty($variant['name'])) {
                    $variants[$index]['name'] = variant_display_name((string) ($variant['id'] ?? ''));
                }
            }

            $availableVariants = [];
            $unavailableVariants = [];
            foreach ($variants as $variant) {
                if (variant_has_stock($variant)) {
                    $availableVariants[] = $variant;
                } else {
                    $unavailableVariants[] = $variant;
                }
            }
            $variants = array_merge($availableVariants, $unavailableVariants);

            $defaultExists = false;
            $defaultIsActive = false;
            foreach ($variants as $variant) {
                if (($variant['id'] ?? '') === $defaultId) {
                    $defaultExists = true;
                    $defaultIsActive = variant_has_stock($variant);
                    break;
                }
            }

            if (!$defaultExists || !$defaultIsActive) {
                $defaultId = '';
                foreach ($variants as $variant) {
                    if (variant_has_stock($variant)) {
                        $defaultId = (string) ($variant['id'] ?? '');
                        break;
                    }
                }
                if ($defaultId === '') {
                    $defaultId = (string) ($variants[0]['id'] ?? '');
                }
            }

            $product['variants'] = $variants;
            $product['default_variant'] = $defaultId;

            $defaultVariant = $this->getDefaultVariant($product);

            if ($defaultVariant !== []) {
                if (isset($defaultVariant['price'])) {
                    $product['price'] = $defaultVariant['price'];
                    $product['price_currency'] = $defaultVariant['price_currency'] ?? 'UAH';
                }
                if (array_key_exists('price_old', $defaultVariant)) {
                    $product['price_old'] = $defaultVariant['price_old'];
                }
            }

            return $product;
        }

        $product['variants'] = [[
            'id' => 'default',
            'name' => '',
            'swatch' => '#d4d4d4',
            'images' => [($product['image'] ?? 'assets/img/placeholder.svg')],
        ]];
        $product['default_variant'] = 'default';

        return $product;
    }
}

<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Repositories\Contracts\CatalogRepositoryInterface;

final class CatalogService
{
    private ?array $catalogCache = null;

    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly LocaleService $locale,
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

        foreach ($product['variants'] ?? [] as $variant) {
            if (($variant['id'] ?? '') === $defaultId) {
                return $variant;
            }
        }

        return $product['variants'][0] ?? [];
    }

    public function findVariant(array $product, string $variantId): ?array
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
                'availability' => 'in stock',
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
                    $variants[$index]['name'] = (string) ($variant['id'] ?? '');
                }
            }

            $availableVariants = [];
            $unavailableVariants = [];
            foreach ($variants as $variant) {
                if (($variant['active'] ?? true) !== false) {
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
                    $defaultIsActive = ($variant['active'] ?? true) !== false;
                    break;
                }
            }

            if (!$defaultExists || !$defaultIsActive) {
                $defaultId = '';
                foreach ($variants as $variant) {
                    if (($variant['active'] ?? true) !== false) {
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

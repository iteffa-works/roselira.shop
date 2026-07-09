<?php

declare(strict_types=1);

namespace Flowaxy\Services;

final class SeoService
{
    public function __construct(private readonly LocaleService $locale)
    {
    }

    /** @param array<string, mixed> $product */
    public function productTitle(array $product, ?float $price, string $currency): string
    {
        $custom = trim((string) ($product['seo_title'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        $brand = trim((string) ($product['brand'] ?? 'KIKO Milano'));
        $name = trim((string) ($product['name'] ?? ''));

        $pricePart = $this->formatPriceForMeta($price, $currency);

        return $this->locale->t('seo_product_title', [
            'brand' => $brand,
            'name' => $name,
            'price' => $pricePart !== '' ? $pricePart : $this->locale->t('seo_price_on_request'),
        ]);
    }

    /** @param array<string, mixed> $product */
    public function productDescription(array $product, ?float $price, string $currency): string
    {
        $custom = trim((string) ($product['seo_description'] ?? ''));
        if ($custom !== '') {
            return $this->truncateMeta($custom, 160);
        }

        $short = trim(strip_tags((string) ($product['short_desc'] ?? '')));
        $brand = trim((string) ($product['brand'] ?? 'KIKO Milano'));
        $name = trim((string) ($product['name'] ?? ''));

        return $this->truncateMeta($this->locale->t('seo_product_desc', [
            'brand' => $brand,
            'name' => $name,
            'short' => $short,
            'price' => $this->formatPriceForMeta($price, $currency) ?: $this->locale->t('seo_price_on_request'),
        ]), 160);
    }

    /** @return array<string, mixed> */
    public function breadcrumbSchema(string $productName, string $slug): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => $this->locale->t('seo_breadcrumb_home'),
                    'item' => absolute_url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $productName,
                    'item' => absolute_url('/' . rawurlencode($slug)),
                ],
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $schemas
     *
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function graph(array $schemas): array
    {
        $normalized = [];

        foreach ($schemas as $schema) {
            if ($schema === []) {
                continue;
            }

            unset($schema['@context']);
            $normalized[] = $schema;
        }

        if ($normalized === []) {
            return [];
        }

        if (count($normalized) === 1) {
            return ['@context' => 'https://schema.org'] + $normalized[0];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $normalized,
        ];
    }

    /** @param list<array<string, mixed>> $products */
    public function homeStructuredData(array $products): array
    {
        $items = [];
        $position = 1;

        foreach ($products as $product) {
            $slug = (string) ($product['slug'] ?? '');
            if ($slug === '') {
                continue;
            }

            $items[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'url' => absolute_url('/' . rawurlencode($slug)),
                'name' => (string) ($product['name'] ?? $slug),
            ];
            $position++;
        }

        $config = app_config();
        $contactEmail = trim((string) ($config['contact_email'] ?? ''));
        $contactPhone = trim((string) ($config['contact_phone'] ?? ''));
        $legalName = trim((string) ($config['business_legal_name'] ?? ''));
        /** @var list<string> $addressLines */
        $addressLines = is_array($config['business_address'] ?? null) ? $config['business_address'] : [];

        $organization = [
            '@type' => 'Organization',
            'name' => $legalName !== '' ? $legalName : 'Roselira',
            'url' => absolute_url('/'),
            'logo' => absolute_url('assets/img/brand/logo-light.svg'),
        ];

        if ($contactEmail !== '') {
            $organization['email'] = $contactEmail;
        }

        if ($contactPhone !== '') {
            $organization['telephone'] = $contactPhone;
        }

        if ($addressLines !== []) {
            $organization['address'] = [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Суми',
                'addressRegion' => 'Сумська область',
                'addressCountry' => 'UA',
            ];
        }

        $schemas = [
            $organization,
            [
                '@type' => 'WebSite',
                'name' => 'Roselira',
                'url' => absolute_url('/'),
                'description' => $this->locale->t('meta_home_desc'),
                'inLanguage' => $this->locale->current() === 'ru' ? 'ru-UA' : 'uk-UA',
            ],
        ];

        if ($items !== []) {
            $schemas[] = [
                '@type' => 'ItemList',
                'name' => $this->locale->t('seo_itemlist_name'),
                'numberOfItems' => count($items),
                'itemListElement' => $items,
            ];
        }

        return $this->graph($schemas);
    }

    /** @return list<array{hreflang: string, href: string}> */
    public function hreflangAlternates(string $canonicalPath): array
    {
        $path = $canonicalPath !== '' ? $canonicalPath : '/';
        $alternates = [];

        foreach ($this->locale->publicLocales() as $lang) {
            $alternates[] = [
                'hreflang' => $lang === 'uk' ? 'uk-UA' : ($lang === 'ru' ? 'ru-UA' : $lang),
                'href' => absolute_url($path . '?lang=' . $lang),
            ];
        }

        $alternates[] = [
            'hreflang' => 'x-default',
            'href' => absolute_url($path . '?lang=' . (string) (app_config()['locale_default'] ?? 'uk')),
        ];

        return $alternates;
    }

    private function formatPriceForMeta(?float $price, string $currency): string
    {
        if ($price === null) {
            return '';
        }

        return formatPrice($price, $currency);
    }

    private function truncateMeta(string $text, int $max): string
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? $text;
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }
}

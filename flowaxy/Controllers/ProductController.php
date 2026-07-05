<?php

declare(strict_types=1);

namespace Flowaxy\Controllers;

use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\CatalogService;
use Flowaxy\Services\LocaleService;

final class ProductController
{
    public function __construct(
        private readonly View $view,
        private readonly LocaleService $locale,
        private readonly CatalogService $catalog,
        private readonly HomeController $home,
    ) {
    }

    public function show(string $slug): Response
    {
        $locale = $this->locale->current();
        $product = $this->catalog->findProduct($slug, $locale);

        if ($product === null) {
            return $this->home->notFoundResponse();
        }

        $defaultVariant = $this->catalog->getDefaultVariant($product);
        $ogImage = $defaultVariant['images'][0] ?? ($product['image'] ?? '');
        $price = $defaultVariant['price'] ?? $product['price'] ?? null;
        $currency = (string) ($defaultVariant['price_currency'] ?? $product['price_currency'] ?? 'UAH');

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['name'] ?? '',
            'description' => $product['short_desc'] ?? '',
            'image' => absolute_url((string) ($defaultVariant['images'][0] ?? $ogImage)),
            'brand' => [
                '@type' => 'Brand',
                'name' => $product['brand'] ?? 'Roselira',
            ],
        ];

        if ($price !== null) {
            $jsonLd['offers'] = [
                '@type' => 'Offer',
                'url' => absolute_url('/' . rawurlencode($slug)),
                'priceCurrency' => $currency,
                'price' => number_format((float) $price, 2, '.', ''),
                'availability' => 'https://schema.org/InStock',
            ];
        }

        return Response::html($this->view->render('layout', [
            'locale' => $locale,
            'title' => ($product['name'] ?? '') . ' — ' . ($product['brand'] ?? 'Roselira'),
            'description' => $product['short_desc'] ?? '',
            'ogImage' => $ogImage,
            'canonicalPath' => '/' . $slug,
            'jsonLd' => $jsonLd,
            'trackingProduct' => [
                'id' => $slug,
                'name' => $product['name'] ?? '',
                'price' => $price,
                'currency' => $currency,
            ],
            'content' => 'product',
            'pageScript' => 'landing',
            'product' => $product,
            'defaultVariant' => $defaultVariant,
            'hasRating' => $this->catalog->productHasRating($product),
            'showOrderForm' => $this->catalog->productUsesOrderForm($product),
            'loadRecaptcha' => recaptcha_enabled() && $this->catalog->productUsesOrderForm($product),
        ]));
    }
}

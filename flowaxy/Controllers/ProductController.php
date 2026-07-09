<?php

declare(strict_types=1);

namespace Flowaxy\Controllers;

use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\CatalogService;
use Flowaxy\Services\LocaleService;
use Flowaxy\Services\ProductRatingService;

final class ProductController
{
    public function __construct(
        private readonly View $view,
        private readonly LocaleService $locale,
        private readonly CatalogService $catalog,
        private readonly ProductRatingService $ratings,
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
        $ratingStats = $this->ratings->resolveForProduct($slug, $product);
        $productForSchema = array_merge($product, [
            'rating' => $ratingStats['rating'],
            'reviews_count' => $ratingStats['reviews_count'],
        ]);

        $jsonLd = $this->catalog->buildProductStructuredData(
            $productForSchema,
            $slug,
            $defaultVariant,
            $price !== null ? (float) $price : null,
            $currency,
        );

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
            'productSlug' => $slug,
            'displayRating' => $ratingStats['rating'],
            'displayReviewCount' => $ratingStats['reviews_count'],
            'userRatingVote' => $ratingStats['user_vote'],
            'hasRating' => $this->catalog->productHasRating($product) || $ratingStats['reviews_count'] > 0,
            'showOrderForm' => $this->catalog->productUsesOrderForm($product),
            'loadRecaptcha' => recaptcha_enabled() && $this->catalog->productUsesOrderForm($product),
        ]));
    }
}

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

        return Response::html($this->view->render('layout', [
            'locale' => $locale,
            'title' => ($product['name'] ?? '') . ' — ' . ($product['brand'] ?? 'Roselira'),
            'description' => $product['short_desc'] ?? '',
            'ogImage' => $defaultVariant['images'][0] ?? ($product['image'] ?? ''),
            'content' => 'product',
            'pageScript' => 'landing',
            'product' => $product,
            'defaultVariant' => $defaultVariant,
            'hasRating' => $this->catalog->productHasRating($product),
            'showOrderForm' => $this->catalog->productUsesOrderForm($product),
        ]));
    }
}

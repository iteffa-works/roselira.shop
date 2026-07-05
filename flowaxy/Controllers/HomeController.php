<?php

declare(strict_types=1);

namespace Flowaxy\Controllers;

use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\CatalogService;
use Flowaxy\Services\LocaleService;

final class HomeController
{
    public function __construct(
        private readonly View $view,
        private readonly LocaleService $locale,
        private readonly CatalogService $catalog,
    ) {
    }

    public function index(): Response
    {
        $locale = $this->locale->current();
        $productGroups = [];

        foreach ($this->catalog->getGroupedActiveProducts($locale) as $groupId => $products) {
            foreach ($products as $product) {
                $product['_hasRating'] = $this->catalog->productHasRating($product);
            }
            $productGroups[$groupId] = $products;
        }

        return Response::html($this->view->render('layout', [
            'locale' => $locale,
            'title' => $this->locale->t('meta_home_title'),
            'description' => $this->locale->t('meta_home_desc'),
            'canonicalPath' => '/',
            'content' => 'home',
            'productGroups' => $productGroups,
        ]));
    }

    public function notFoundResponse(): Response
    {
        return Response::html($this->view->render('layout', [
            'locale' => $this->locale->current(),
            'title' => $this->locale->t('meta_not_found_title'),
            'description' => $this->locale->t('meta_not_found_desc'),
            'content' => 'not-found',
        ]), 404);
    }
}

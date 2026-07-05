<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Response;

final class CatalogController extends CatalogAdminController
{
    public function index(): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $rows = [];
        foreach ($this->catalog->loadProducts() as $slug => $product) {
            $rows[] = [
                'slug' => $slug,
                'product' => $product,
                'price' => $this->catalog->localizeProduct($product, 'uk')['price'] ?? null,
            ];
        }

        $content = $this->view->renderAdmin('catalog', [
            'rows' => $rows,
        ]);

        return $this->renderPage($content, 'Каталог', 'catalog');
    }
}

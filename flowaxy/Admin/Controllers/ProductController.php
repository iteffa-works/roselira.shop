<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\CatalogService;
use Flowaxy\Services\ExchangeService;
use Flowaxy\Services\LocaleService;

final class ProductController extends CatalogAdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        CatalogService $catalog,
        private readonly ExchangeService $exchange,
        private readonly LocaleService $locale,
    ) {
        parent::__construct($view, $auth, $catalog);
    }

    public function edit(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $resolved = $this->resolveProduct($request);
        if ($resolved === null) {
            $this->auth->flash('error', 'Товар не знайдено.');

            return $this->redirect(admin_url('catalog'));
        }

        $this->catalog->ensureCategoriesBootstrapped();

        $content = $this->view->renderAdmin('product', [
            'slug' => $resolved['slug'],
            'product' => $resolved['product'],
            'rates' => $this->exchange->getRates(),
            'csrf' => $this->auth->csrfToken(),
            'editableLocales' => $this->locale->editableLocales(),
            'localeLabels' => $this->locale->adminLocaleLabels(),
            'categories' => $this->catalog->loadCategories(),
        ]);

        return $this->renderPage($content, 'Товар', 'catalog');
    }

    public function update(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $resolved = $this->resolveProduct($request);
        if ($resolved === null) {
            $this->auth->flash('error', 'Товар не знайдено.');

            return $this->redirect(admin_url('catalog'));
        }

        $slug = $resolved['slug'];
        $product = $resolved['product'];
        $catalog = $this->catalog->loadCatalog();
        $products = $catalog['products'] ?? [];

        $product['active'] = $request->post('active') !== null;
        $product['default_variant'] = trim((string) $request->post('default_variant', $product['default_variant'] ?? ''));
        $categoryId = $this->catalog->normalizeCategoryId((string) $request->post('category_id', ''));
        $category = $categoryId !== '' ? $this->catalog->findCategory($categoryId) : null;
        if ($category !== null) {
            $product['category_id'] = $categoryId;
            $labels = is_array($category['labels'] ?? null) ? $category['labels'] : [];
            foreach (['uk', 'ru', 'en'] as $loc) {
                $label = trim((string) ($labels[$loc] ?? ''));
                if ($label !== '') {
                    $product['i18n'][$loc]['category'] = $label;
                }
            }
            $gpc = trim((string) ($category['google_product_category'] ?? ''));
            if ($gpc !== '') {
                $product['google_product_category'] = $gpc;
            } else {
                unset($product['google_product_category']);
            }
        } else {
            unset($product['category_id']);
        }
        $inventoryNote = trim((string) $request->post('inventory_note', ''));
        if ($inventoryNote !== '') {
            $product['inventory_note'] = $inventoryNote;
        } else {
            unset($product['inventory_note']);
        }

        foreach ($this->locale->editableLocales() as $loc) {
            $product['i18n'][$loc]['name'] = trim((string) $request->post("name_$loc", ''));
            $product['i18n'][$loc]['short_desc'] = trim((string) $request->post("short_desc_$loc", ''));
            $product['i18n'][$loc]['description'] = trim((string) $request->post("description_$loc", ''));
            $benefitsRaw = trim((string) $request->post("benefits_$loc", ''));
            $product['i18n'][$loc]['benefits'] = array_values(array_filter(array_map('trim', explode("\n", $benefitsRaw))));
        }

        $variantIds = $request->post('variant_id', []);
        $variantActive = $request->post('variant_active', []);
        $variantPriceEur = $request->post('variant_price_eur', []);
        $variantPriceUsd = $request->post('variant_price_usd', []);
        $variantStock = $request->post('variant_stock', []);

        if (is_array($product['variants'] ?? null) && is_array($variantIds)) {
            foreach ($product['variants'] as $index => $variant) {
                $vid = (string) ($variant['id'] ?? '');
                if (!in_array($vid, $variantIds, true)) {
                    continue;
                }

                $product['variants'][$index]['active'] = is_array($variantActive) && isset($variantActive[$vid]);
                $stockRaw = trim((string) (is_array($variantStock) ? ($variantStock[$vid] ?? '') : ''));
                if ($stockRaw === '') {
                    unset($product['variants'][$index]['stock']);
                } elseif (is_numeric($stockRaw)) {
                    $product['variants'][$index]['stock'] = max(0, (int) $stockRaw);
                }
                $eur = trim((string) (is_array($variantPriceEur) ? ($variantPriceEur[$vid] ?? '') : ''));
                $usd = trim((string) (is_array($variantPriceUsd) ? ($variantPriceUsd[$vid] ?? '') : ''));

                if ($eur !== '' && is_numeric($eur)) {
                    $product['variants'][$index]['price_eur'] = (float) $eur;
                    unset($product['variants'][$index]['price_usd']);
                } elseif ($usd !== '' && is_numeric($usd)) {
                    $product['variants'][$index]['price_usd'] = (float) $usd;
                    unset($product['variants'][$index]['price_eur']);
                }
            }
        }

        $products[$slug] = $product;
        $catalog['products'] = $products;
        $catalog = $this->exchange->recalculateCatalogPrices($catalog);

        if ($this->catalog->saveCatalog($catalog)) {
            $this->auth->flash('success', 'Товар збережено.');
        } else {
            $this->auth->flash('error', 'Помилка збереження.');
        }

        return $this->redirect(admin_url('product', ['slug' => $slug]));
    }
}

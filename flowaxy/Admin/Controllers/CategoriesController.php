<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;

final class CategoriesController extends CatalogAdminController
{
    public function index(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $this->catalog->ensureCategoriesBootstrapped();

        $editId = $this->catalog->normalizeCategoryId((string) $request->query('edit', ''));
        $categories = $this->catalog->loadCategories();
        $edit = $editId !== '' && isset($categories[$editId])
            ? ['id' => $editId] + $categories[$editId]
            : null;

        $usage = [];
        foreach ($this->catalog->loadProducts() as $product) {
            $cid = (string) ($product['category_id'] ?? '');
            if ($cid === '') {
                continue;
            }
            $usage[$cid] = ($usage[$cid] ?? 0) + 1;
        }

        $content = $this->view->renderAdmin('categories', [
            'categories' => $categories,
            'usage' => $usage,
            'edit' => $edit,
            'csrf' => $this->auth->csrfToken(),
        ]);

        return $this->renderPage($content, 'Категорії', 'categories');
    }

    public function save(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $action = trim((string) $request->post('action', 'save'));
        if ($action === 'delete') {
            $result = $this->catalog->deleteCategory((string) $request->post('id', ''));
            $this->auth->flash($result['ok'] ? 'success' : 'error', $result['message']);

            return $this->redirect(admin_url('categories'));
        }

        $originalId = trim((string) $request->post('original_id', ''));
        $id = trim((string) $request->post('id', $originalId));
        $result = $this->catalog->upsertCategory($id, [
            'order' => (int) $request->post('order', 999),
            'google_product_category' => (string) $request->post('google_product_category', ''),
            'labels' => [
                'uk' => (string) $request->post('label_uk', ''),
                'ru' => (string) $request->post('label_ru', ''),
                'en' => (string) $request->post('label_en', ''),
            ],
        ], $originalId !== '' ? $originalId : null);

        $this->auth->flash($result['ok'] ? 'success' : 'error', $result['message']);

        if ($result['ok'] && isset($result['id'])) {
            return $this->redirect(admin_url('categories', ['edit' => $result['id']]));
        }

        return $this->redirect(admin_url('categories', $originalId !== '' ? ['edit' => $originalId] : []));
    }
}

<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\CatalogService;

abstract class CatalogAdminController extends AdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        protected readonly CatalogService $catalog,
    ) {
        parent::__construct($view, $auth);
    }

    /** @return array{slug: string, product: array<string, mixed>}|null */
    protected function resolveProduct(Request $request): ?array
    {
        $slug = trim((string) ($request->query('slug', '') ?: $request->post('slug', '')));

        return $this->catalog->resolveRawProduct($slug);
    }
}

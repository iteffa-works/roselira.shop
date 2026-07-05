<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\CatalogService;
use Flowaxy\Services\ExchangeService;
use Flowaxy\Services\OrderService;

final class DashboardController extends CatalogAdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        CatalogService $catalog,
        private readonly OrderService $orders,
        private readonly ExchangeService $exchange,
    ) {
        parent::__construct($view, $auth, $catalog);
    }

    public function index(): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $orders = $this->orders->all();
        $newCount = count(array_filter($orders, static fn(array $o): bool => ($o['status'] ?? '') === 'new'));
        $products = $this->catalog->loadProducts();
        $activeCount = count(array_filter($products, static fn(array $p): bool => ($p['active'] ?? false) === true));

        $content = $this->view->renderAdmin('dashboard', [
            'newCount' => $newCount,
            'ordersCount' => count($orders),
            'activeCount' => $activeCount,
            'rates' => $this->exchange->getRates(),
            'recentOrders' => array_slice($orders, 0, 5),
        ]);

        return $this->renderPage($content, 'Dashboard', 'dashboard');
    }
}

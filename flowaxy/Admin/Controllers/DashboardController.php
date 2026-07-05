<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\CatalogService;
use Flowaxy\Services\ExchangeService;
use Flowaxy\Services\OrderService;
use Flowaxy\Services\VisitorAnalyticsService;

final class DashboardController extends CatalogAdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        CatalogService $catalog,
        private readonly OrderService $orders,
        private readonly ExchangeService $exchange,
        private readonly VisitorAnalyticsService $analytics,
    ) {
        parent::__construct($view, $auth, $catalog);
    }

    public function index(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $orders = $this->orders->all();
        $newCount = count(array_filter($orders, static fn(array $o): bool => ($o['status'] ?? '') === 'new'));
        $products = $this->catalog->loadProducts();
        $activeCount = count(array_filter($products, static fn(array $p): bool => ($p['active'] ?? false) === true));

        $days = max(1, min(90, (int) $request->query('days', 7)));
        $heatmapPath = (string) $request->query('page', '/');
        $analytics = $this->analytics->dashboard($days, $heatmapPath);

        $content = $this->view->renderAdmin('dashboard', [
            'newCount' => $newCount,
            'ordersCount' => count($orders),
            'activeCount' => $activeCount,
            'rates' => $this->exchange->getRates(),
            'recentOrders' => array_slice($orders, 0, 5),
            'analytics' => $analytics,
        ]);

        return $this->renderPage($content, 'Dashboard', 'dashboard');
    }
}

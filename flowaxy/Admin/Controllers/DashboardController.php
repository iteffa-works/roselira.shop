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
use Flowaxy\Services\GoogleAnalyticsService;
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
        private readonly GoogleAnalyticsService $googleAnalytics,
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
        $source = (string) $request->query('source', 'local');
        if (!in_array($source, ['local', 'google'], true)) {
            $source = 'local';
        }
        if ($source === 'google' && !$this->googleAnalytics->canShowGoogleTab()) {
            $source = 'local';
        }

        $analytics = $this->analytics->dashboard($days);
        $googleReport = $source === 'google' ? $this->googleAnalytics->dashboard($days) : null;

        $content = $this->view->renderAdmin('dashboard', [
            'newCount' => $newCount,
            'ordersCount' => count($orders),
            'activeCount' => $activeCount,
            'rates' => $this->exchange->getRates(),
            'recentOrders' => array_slice($orders, 0, 5),
            'analytics' => $analytics,
            'analyticsSource' => $source,
            'googleReport' => $googleReport,
            'googleTabAvailable' => $this->googleAnalytics->canShowGoogleTab(),
        ]);

        return $this->renderPage($content, 'Dashboard', 'dashboard');
    }

    public function heatmap(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $days = max(1, min(90, (int) $request->query('days', 7)));
        $heatmapPath = (string) $request->query('page', '/');
        $viewport = (string) $request->query('viewport', '');
        $analytics = $this->analytics->heatmapPage($days, $heatmapPath, $viewport);

        $content = $this->view->renderAdmin('heatmap', [
            'analytics' => $analytics,
            'csrf' => $this->auth->csrfToken(),
        ]);

        return Response::html($this->view->renderAdmin('layout_tool', [
            'content' => $content,
            'title' => 'Heatmap кліків',
            'flash' => $this->auth->getFlash(),
        ]));
    }

    public function heatmapCleanup(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        try {
            $result = $this->analytics->purgeFromRequest($request);
            if ($result === null) {
                $this->auth->flash('error', 'Невідомий параметр очистки.');

                return $this->redirectToHeatmap($request);
            }

            $this->auth->flash(
                'success',
                sprintf('Видалено %d подій та %d сесій.', $result['events'], $result['sessions']),
            );
        } catch (\Throwable) {
            $this->auth->flash('error', 'Не вдалося очистити дані аналітики.');
        }

        return $this->redirectToHeatmap($request);
    }

    private function redirectToHeatmap(Request $request): Response
    {
        $days = max(1, min(90, (int) $request->post('days', $request->query('days', 7))));
        $page = (string) $request->post('page', $request->query('page', '/'));
        $viewport = (string) $request->post('viewport', $request->query('viewport', ''));

        return $this->redirect(admin_url('heatmap', [
            'days' => $days,
            'page' => $page,
            'viewport' => $viewport,
        ]));
    }

    public function googleAnalytics(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if (!$this->googleAnalytics->hasApiAccess()) {
            return Response::json(['error' => 'GA4 API не налаштовано'], 400);
        }

        if ($request->query('live') === '1') {
            $live = $this->googleAnalytics->liveSnapshot();

            return Response::json([
                'live' => $live ?? ['active_users' => 0, 'event_count' => 0],
            ]);
        }

        $days = max(1, min(90, (int) $request->query('days', 1)));

        return Response::json($this->googleAnalytics->dashboard($days));
    }
}

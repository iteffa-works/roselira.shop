<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Repositories\Sqlite\Connection;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\OrderService;
use Flowaxy\Services\VisitorAnalyticsService;

final class DatabaseController extends AdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        private readonly OrderService $orders,
        private readonly Connection $connection,
        private readonly VisitorAnalyticsService $analytics,
    ) {
        parent::__construct($view, $auth);
    }

    public function index(): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $content = $this->view->renderAdmin('database', [
            'counts' => $this->connection->tableCounts(),
            'orderCounts' => $this->orders->countByStatus(),
            'dbSize' => $this->connection->dbFileSize(),
            'statuses' => $this->orders->statuses(),
            'csrf' => $this->auth->csrfToken(),
        ]);

        return $this->renderPage($content, 'База даних', 'database');
    }

    public function cleanup(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $action = trim((string) $request->post('action', ''));
        $deleted = 0;

        switch ($action) {
            case 'delete_cancelled':
                $deleted = $this->orders->deleteByStatuses(['cancelled']);
                $this->auth->flash('success', "Видалено {$deleted} скасованих замовлень.");
                break;

            case 'delete_done':
                $deleted = $this->orders->deleteByStatuses(['done']);
                $this->auth->flash('success', "Видалено {$deleted} виконаних замовлень.");
                break;

            case 'delete_closed':
                $deleted = $this->orders->deleteByStatuses(['done', 'cancelled']);
                $this->auth->flash('success', "Видалено {$deleted} закритих замовлень.");
                break;

            case 'delete_all_orders':
                $deleted = $this->orders->deleteAll();
                $this->auth->flash('success', "Видалено всі замовлення ({$deleted}).");
                break;

            case 'purge_orders':
                $deleted = $this->purgeOrders($request);
                if ($deleted !== null) {
                    $this->auth->flash('success', "Видалено {$deleted} замовлень.");
                }
                break;

            case 'purge_analytics':
                $result = $this->purgeAnalytics($request);
                if ($result !== null) {
                    $this->auth->flash(
                        'success',
                        sprintf('Видалено %d подій та %d сесій.', $result['events'], $result['sessions']),
                    );
                }
                break;

            case 'vacuum':
                $this->connection->vacuum();
                $this->auth->flash('success', 'Базу даних оптимізовано (VACUUM).');
                break;

            default:
                $this->auth->flash('error', 'Невідома дія.');
        }

        return $this->redirect(admin_url('database'));
    }

    private function purgeOrders(Request $request): ?int
    {
        $scope = (string) $request->post('scope', '');
        if (!in_array($scope, ['all', 'within_last', 'older_than'], true)) {
            $this->auth->flash('error', 'Невідомий параметр очистки замовлень.');

            return null;
        }

        $periodDays = max(1, min(3650, (int) $request->post('period_days', 7)));
        $statuses = $this->selectedStatuses($request);

        try {
            return $this->orders->deleteByPeriod($scope, $periodDays, $statuses);
        } catch (\Throwable) {
            $this->auth->flash('error', 'Не вдалося очистити замовлення.');

            return null;
        }
    }

    /** @return array{events: int, sessions: int}|null */
    private function purgeAnalytics(Request $request): ?array
    {
        $scope = (string) $request->post('scope', '');
        if (!in_array($scope, ['all', 'within_last', 'older_than'], true)) {
            $this->auth->flash('error', 'Невідомий параметр очистки аналітики.');

            return null;
        }

        $periodDays = max(1, min(3650, (int) $request->post('period_days', 7)));
        $path = $request->post('filter_page') === '1'
            ? (string) $request->post('page', '/')
            : null;
        $viewport = $request->post('filter_viewport') === '1'
            ? (string) $request->post('viewport', '')
            : null;
        $eventTypes = $request->post('clicks_only') === '1' ? ['click'] : null;

        try {
            return $this->analytics->purgeAnalytics($scope, $periodDays, $path, $viewport, $eventTypes);
        } catch (\Throwable) {
            $this->auth->flash('error', 'Не вдалося очистити дані аналітики.');

            return null;
        }
    }

    /** @return list<string>|null */
    private function selectedStatuses(Request $request): ?array
    {
        if ($request->post('filter_status') !== '1') {
            return null;
        }

        $statuses = [];
        foreach ($this->orders->statuses() as $status) {
            if ($request->post('status_' . $status) === '1') {
                $statuses[] = $status;
            }
        }

        return $statuses === [] ? null : $statuses;
    }
}

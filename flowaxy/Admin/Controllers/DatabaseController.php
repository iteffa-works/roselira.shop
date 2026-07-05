<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Repositories\Sqlite\Connection;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\OrderService;

final class DatabaseController extends AdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        private readonly OrderService $orders,
        private readonly Connection $connection,
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

            case 'vacuum':
                $this->connection->vacuum();
                $this->auth->flash('success', 'Базу даних оптимізовано (VACUUM).');
                break;

            default:
                $this->auth->flash('error', 'Невідома дія.');
        }

        return $this->redirect(admin_url('database'));
    }
}

<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\OrderService;

final class OrdersController extends AdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        private readonly OrderService $orders,
    ) {
        parent::__construct($view, $auth);
    }

    public function index(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $statuses = $this->orders->statuses();
        $filter = trim((string) $request->query('status', ''));
        $orders = $this->orders->all();

        if ($filter !== '' && in_array($filter, $statuses, true)) {
            $orders = array_values(array_filter($orders, static fn(array $o): bool => ($o['status'] ?? '') === $filter));
        }

        $content = $this->view->renderAdmin('orders', [
            'orders' => $orders,
            'filter' => $filter,
            'statuses' => $statuses,
            'csrf' => $this->auth->csrfToken(),
        ]);

        return $this->renderPage($content, 'Замовлення', 'orders');
    }

    public function delete(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $orderId = trim((string) $request->post('order_id', ''));

        if ($orderId !== '' && $this->orders->deleteById(basename($orderId))) {
            $this->auth->flash('success', 'Замовлення видалено.');
        } else {
            $this->auth->flash('error', 'Не вдалося видалити замовлення.');
        }

        return $this->redirect($this->ordersListUrl($request));
    }

    public function updateStatus(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $orderId = trim((string) $request->post('order_id', ''));
        $status = trim((string) $request->post('status', ''));

        if ($orderId !== '' && $this->orders->isValidStatus($status)) {
            $order = $this->orders->findById(basename($orderId));
            if ($order !== null) {
                $order['status'] = $status;
                if ($this->orders->save($order)) {
                    $this->auth->flash('success', 'Статус оновлено.');
                }
            }
        }

        return $this->redirect($this->ordersListUrl($request));
    }

    private function ordersListUrl(Request $request): string
    {
        $filter = $request->query('status');
        $query = is_string($filter) && $filter !== '' ? ['status' => $filter] : [];

        return admin_url('orders', $query);
    }
}

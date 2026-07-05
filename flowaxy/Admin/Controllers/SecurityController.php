<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\SecurityLogService;
use Flowaxy\Support\RequestContext;

final class SecurityController extends AdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        private readonly SecurityLogService $security,
    ) {
        parent::__construct($view, $auth);
    }

    public function index(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $filters = [
            'ip' => trim((string) $request->query('ip', '')),
            'event_type' => trim((string) $request->query('event_type', '')),
            'verdict' => trim((string) $request->query('verdict', '')),
            'q' => trim((string) $request->query('q', '')),
        ];

        $content = $this->view->renderAdmin('security', [
            'stats' => $this->security->stats(),
            'events' => $this->security->listEvents($filters, 150),
            'filters' => $filters,
            'eventLabels' => SecurityLogService::eventLabels(),
            'verdictLabels' => SecurityLogService::verdictLabels(),
            'csrf' => $this->auth->csrfToken(),
        ]);

        return $this->renderPage($content, 'Безпека', 'security');
    }

    public function action(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $action = trim((string) $request->post('action', ''));

        switch ($action) {
            case 'clear_rate_limits':
                $count = $this->security->clearAllRateLimits();
                $this->auth->flash('success', "Скинуто {$count} записів лімітів (IP).");
                break;

            case 'clear_rate_ip':
                $ip = trim((string) $request->post('ip', ''));
                if ($ip === '') {
                    $this->auth->flash('error', 'Вкажіть IP.');
                    break;
                }
                $count = $this->security->clearOrderLimit($ip) + $this->security->clearLoginLimit($ip);
                $this->auth->flash('success', "Ліміт для {$ip} скинуто ({$count}).");
                break;

            case 'clear_logs_30':
                $count = $this->security->deleteEventsOlderThan(30);
                $this->auth->flash('success', "Видалено {$count} записів логів (старіші 30 днів).");
                break;

            case 'clear_logs_all':
                $count = $this->security->deleteAllEvents();
                $this->auth->flash('success', "Очищено {$count} записів логів.");
                break;

            default:
                $this->auth->flash('error', 'Невідома дія.');
        }

        $query = array_filter([
            'ip' => trim((string) $request->post('filter_ip', '')),
            'event_type' => trim((string) $request->post('filter_event_type', '')),
            'verdict' => trim((string) $request->post('filter_verdict', '')),
            'q' => trim((string) $request->post('filter_q', '')),
        ]);

        return $this->redirect(admin_url('security', $query));
    }
}

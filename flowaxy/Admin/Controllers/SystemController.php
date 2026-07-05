<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\CronService;
use Flowaxy\Services\GitUpdateService;
use Flowaxy\Services\SystemCheckService;

final class SystemController extends AdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        private readonly GitUpdateService $gitUpdate,
        private readonly SystemCheckService $systemCheck,
        private readonly CronService $cron,
    ) {
        parent::__construct($view, $auth);
    }

    public function index(): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $config = app_config();
        $checks = $this->systemCheck->lastResults() ?? $this->systemCheck->runAll();

        $content = $this->view->renderAdmin('system', [
            'gitStatus' => $this->gitUpdate->getStatus(),
            'cronStatus' => $this->cron->getCronStatus(),
            'checks' => $checks,
            'csrf' => $this->auth->csrfToken(),
            'projectRoot' => (string) ($config['project_root'] ?? ''),
            'feedSecret' => (string) ($config['feed_secret'] ?? ''),
            'gitRepoUrl' => (string) ($config['git_repo_url'] ?? ''),
        ]);

        return $this->renderPage($content, 'Система', 'system');
    }

    public function gitPull(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $result = $this->gitUpdate->pull(forcePull: true);
        $this->auth->flash($result['success'] ? 'success' : 'error', $result['message']);

        return $this->redirect(admin_url('system'));
    }

    public function runChecks(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $checks = $this->systemCheck->runAll();
        $summary = $checks['summary'];
        $this->auth->flash(
            ($summary['error'] ?? 0) > 0 ? 'error' : 'success',
            sprintf('Перевірка: OK %d, попереджень %d, помилок %d', $summary['ok'], $summary['warn'], $summary['error']),
        );

        return $this->redirect(admin_url('system'));
    }

    public function runCron(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $result = $this->cron->runDaily(forceDaily: false);
        $this->auth->flash($result['success'] ? 'success' : 'error', $result['message']);

        return $this->redirect(admin_url('system'));
    }
}

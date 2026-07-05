<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;

abstract class AdminController
{
    public function __construct(
        protected readonly View $view,
        protected readonly AdminAuthService $auth,
    ) {
    }

    protected function renderPage(string $content, string $title, string $page = ''): Response
    {
        return Response::html($this->view->renderAdmin('layout', [
            'content' => $content,
            'title' => $title,
            'page' => $page,
            'flash' => $this->auth->getFlash(),
        ]));
    }

    protected function requireAuth(): ?Response
    {
        if ($response = $this->auth->ensureConfigured()) {
            return $response;
        }

        return $this->auth->requireLogin();
    }

    protected function verifyPost(Request $request): ?Response
    {
        return $this->auth->verifyPost($request);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect($path);
    }
}

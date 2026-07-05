<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\TelegramNotificationService;

final class NotificationsController extends AdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        private readonly TelegramNotificationService $telegram,
    ) {
        parent::__construct($view, $auth);
    }

    public function index(): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        $content = $this->view->renderAdmin('notifications', [
            'config' => $this->telegram->getConfig(),
            'csrf' => $this->auth->csrfToken(),
        ]);

        return $this->renderPage($content, 'Сповіщення', 'notifications');
    }

    public function save(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $saved = $this->telegram->saveConfig([
            'enabled' => $request->post('enabled') !== null,
            'bot_token' => trim((string) $request->post('bot_token', '')),
            'chat_id' => trim((string) $request->post('chat_id', '')),
            'is_forum' => $request->post('is_forum') !== null,
            'thread_id' => trim((string) $request->post('thread_id', '')),
        ]);

        $this->auth->flash($saved ? 'success' : 'error', $saved ? 'Налаштування збережено.' : 'Не вдалося зберегти.');

        return $this->redirect(admin_url('notifications'));
    }

    public function test(Request $request): Response
    {
        if ($response = $this->requireAuth()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        if ($this->telegram->sendTestMessage()) {
            $this->auth->flash('success', 'Тестове повідомлення надіслано.');
        } else {
            $this->auth->flash('error', 'Не вдалося надіслати. Перевірте token, chat ID і права бота.');
        }

        return $this->redirect(admin_url('notifications'));
    }
}

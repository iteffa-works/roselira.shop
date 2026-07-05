<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Core\View;
use Flowaxy\Services\AdminAuthService;
use Flowaxy\Services\RecaptchaService;
use Flowaxy\Services\SecurityLogService;
use Flowaxy\Support\LoginRateLimiter;

final class AuthController extends AdminController
{
    public function __construct(
        View $view,
        AdminAuthService $auth,
        private readonly LoginRateLimiter $loginRateLimiter,
        private readonly SecurityLogService $security,
        private readonly RecaptchaService $recaptcha,
    ) {
        parent::__construct($view, $auth);
    }

    public function loginForm(): Response
    {
        if ($response = $this->auth->ensureConfigured()) {
            return $response;
        }

        if ($this->auth->isLoggedIn()) {
            return $this->redirect(admin_url());
        }

        return Response::html($this->view->renderAdmin('layout', [
            'template' => 'login',
            'title' => 'Вхід',
            'error' => '',
            'csrf' => $this->auth->csrfToken(),
        ]));
    }

    public function login(Request $request): Response
    {
        if ($response = $this->auth->ensureConfigured()) {
            return $response;
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        if ($this->loginRateLimiter->isLimited()) {
            $this->security->log('login_rate_limited', 'fraud', [
                'message' => 'IP rate limit',
            ]);

            return $this->loginError('Забагато спроб. Спробуйте через 15 хвилин.');
        }

        if (!$this->recaptcha->verifyRequest($request)) {
            $this->security->log('login_captcha_failed', 'fraud', [
                'message' => 'reCAPTCHA failed',
            ]);

            return $this->loginError('Підтвердіть перевірку reCAPTCHA.');
        }

        $username = trim((string) $request->post('username', ''));

        if ($this->auth->login(
            $username,
            (string) $request->post('password', ''),
        )) {
            $this->loginRateLimiter->clear();
            $this->security->log('login_success', 'ok', ['username' => $username]);

            return $this->redirect(admin_url());
        }

        $this->loginRateLimiter->hit();
        $this->security->log('login_failed', 'suspect', ['username' => $username]);

        return $this->loginError('Невірний логін або пароль.');
    }

    private function loginError(string $error): Response
    {
        return Response::html($this->view->renderAdmin('layout', [
            'template' => 'login',
            'title' => 'Вхід',
            'error' => $error,
            'csrf' => $this->auth->csrfToken(),
        ]));
    }

    public function logout(): Response
    {
        $this->auth->logout();

        return $this->redirect(admin_url('login'));
    }

    public function installForm(): Response
    {
        if ($response = $this->installUnavailableResponse()) {
            return $response;
        }

        if ($this->auth->isConfigured()) {
            return $this->redirect(admin_url('login'));
        }

        return Response::html($this->view->renderAdmin('layout', [
            'template' => 'install',
            'title' => 'Налаштування адмінки',
            'error' => '',
            'csrf' => $this->auth->csrfToken(),
        ]));
    }

    public function install(Request $request): Response
    {
        if ($response = $this->installUnavailableResponse()) {
            return $response;
        }

        if ($this->auth->isConfigured()) {
            return $this->redirect(admin_url('login'));
        }

        if ($response = $this->verifyPost($request)) {
            return $response;
        }

        $username = trim((string) $request->post('username', 'admin'));
        $password = (string) $request->post('password', '');
        $confirm = (string) $request->post('password_confirm', '');
        $error = '';

        if ($username === '' || strlen($password) < 6) {
            $error = 'Логін і пароль (мін. 6 символів) обов\'язкові.';
        } elseif ($password !== $confirm) {
            $error = 'Паролі не збігаються.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            if ($this->auth->saveCredentials($username, $hash) && $this->auth->login($username, $password)) {
                $this->auth->flash('success', 'Адмінку налаштовано.');

                return $this->redirect(admin_url());
            }

            $error = 'Не вдалося зберегти облікові дані. Перевірте права на storage/.';
        }

        return Response::html($this->view->renderAdmin('layout', [
            'template' => 'install',
            'title' => 'Налаштування адмінки',
            'error' => $error,
            'csrf' => $this->auth->csrfToken(),
        ]));
    }

    private function installUnavailableResponse(): ?Response
    {
        if (flowaxy_env('APP_ENV', 'local') === 'production' && $this->auth->isConfigured()) {
            return Response::html('Not found', 404);
        }

        return null;
    }
}

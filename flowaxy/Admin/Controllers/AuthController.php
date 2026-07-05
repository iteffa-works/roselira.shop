<?php

declare(strict_types=1);

namespace Flowaxy\Admin\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;

final class AuthController extends AdminController
{
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

        if ($this->auth->login(
            trim((string) $request->post('username', '')),
            (string) $request->post('password', ''),
        )) {
            return $this->redirect(admin_url());
        }

        return Response::html($this->view->renderAdmin('layout', [
            'template' => 'login',
            'title' => 'Вхід',
            'error' => 'Невірний логін або пароль.',
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
        if (flowaxy_env('APP_ENV', 'local') === 'production') {
            return Response::html('Not found', 404);
        }

        return null;
    }
}

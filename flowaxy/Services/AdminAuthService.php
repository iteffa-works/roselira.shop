<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Repositories\Contracts\AdminUserRepositoryInterface;

final class AdminAuthService
{
    public function __construct(
        private readonly AdminUserRepositoryInterface $adminUsers,
        private readonly string $sessionKey,
        private readonly bool $sessionSecure = false,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->adminUsers->isConfigured();
    }

    public function ensureConfigured(): ?Response
    {
        if (!$this->isConfigured()) {
            return Response::redirect('/admin/install');
        }

        return null;
    }

    public function isLoggedIn(): bool
    {
        $this->startSession();

        return ($_SESSION[$this->sessionKey] ?? false) === true;
    }

    public function requireLogin(): ?Response
    {
        if (!$this->isLoggedIn()) {
            return Response::redirect('/admin/login');
        }

        return null;
    }

    public function login(string $username, string $password): bool
    {
        $config = $this->adminUsers->loadCredentials();
        $expectedUser = (string) ($config['username'] ?? '');
        $hash = (string) ($config['password_hash'] ?? '');

        if ($expectedUser === '' || $hash === '') {
            return false;
        }

        if (!hash_equals($expectedUser, $username) || !password_verify($password, $hash)) {
            return false;
        }

        $this->startSession();
        session_regenerate_id(true);
        $_SESSION[$this->sessionKey] = true;

        return true;
    }

    public function logout(): void
    {
        $this->startSession();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly'],
            );
        }

        session_destroy();
    }

    public function csrfToken(): string
    {
        $this->startSession();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    public function verifyCsrf(?string $token): bool
    {
        $this->startSession();

        return is_string($token) && $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }

    public function verifyPost(Request $request): ?Response
    {
        if (!$request->isPost()) {
            return Response::html('Method not allowed', 405);
        }

        if (!$this->verifyCsrf((string) $request->post('csrf', ''))) {
            $this->flash('error', 'Невірний CSRF-токен.');

            return Response::redirect(admin_url());
        }

        return null;
    }

    public function flash(string $type, string $message): void
    {
        $this->startSession();
        $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
    }

    public function getFlash(): ?array
    {
        $this->startSession();
        $flash = $_SESSION['admin_flash'] ?? null;
        unset($_SESSION['admin_flash']);

        return is_array($flash) ? $flash : null;
    }

    public function saveCredentials(string $username, string $passwordHash): bool
    {
        return $this->adminUsers->saveCredentials($username, $passwordHash);
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_secure' => $this->sessionSecure,
            ]);
        }
    }
}

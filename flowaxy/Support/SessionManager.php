<?php

declare(strict_types=1);

namespace Flowaxy\Support;

final class SessionManager
{
    /** @param array{session_secure?: bool} $config */
    public static function ensureStarted(array $config = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (bool) ($config['session_secure'] ?? false);

        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => $secure,
        ]);
    }
}

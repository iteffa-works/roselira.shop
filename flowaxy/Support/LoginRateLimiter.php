<?php

declare(strict_types=1);

namespace Flowaxy\Support;

final class LoginRateLimiter
{
    private const SESSION_KEY = 'flowaxy_login_attempts';
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 900;

    public function isLimited(): bool
    {
        $attempts = $_SESSION[self::SESSION_KEY] ?? [];
        if (!is_array($attempts)) {
            return false;
        }

        $now = time();
        $recent = array_values(array_filter(
            $attempts,
            static fn(mixed $timestamp): bool => is_int($timestamp) && ($now - $timestamp) < self::WINDOW_SECONDS,
        ));

        $_SESSION[self::SESSION_KEY] = $recent;

        return count($recent) >= self::MAX_ATTEMPTS;
    }

    public function hit(): void
    {
        $attempts = $_SESSION[self::SESSION_KEY] ?? [];
        if (!is_array($attempts)) {
            $attempts = [];
        }

        $attempts[] = time();
        $_SESSION[self::SESSION_KEY] = $attempts;
    }

    public function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }
}

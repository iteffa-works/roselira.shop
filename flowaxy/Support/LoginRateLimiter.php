<?php

declare(strict_types=1);

namespace Flowaxy\Support;

use Flowaxy\Services\SecurityLogService;

final class LoginRateLimiter
{
    public function __construct(private readonly SecurityLogService $security)
    {
    }

    public function isLimited(): bool
    {
        return $this->security->isLoginLimited();
    }

    public function hit(): void
    {
        $this->security->hitLogin();
    }

    public function clear(): void
    {
        $this->security->clearLoginLimit();
    }
}

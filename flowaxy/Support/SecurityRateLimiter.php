<?php

declare(strict_types=1);

namespace Flowaxy\Support;

use Flowaxy\Services\SecurityLogService;

class SecurityRateLimiter
{
    public const SCOPE_LOGIN = 'login';
    public const SCOPE_ORDER = 'order';

    public function __construct(
        private readonly SecurityLogService $security,
        private readonly string $scope,
    ) {
    }

    public function isLimited(): bool
    {
        return match ($this->scope) {
            self::SCOPE_LOGIN => $this->security->isLoginLimited(),
            self::SCOPE_ORDER => $this->security->isOrderLimited(),
            default => false,
        };
    }

    public function hit(): void
    {
        match ($this->scope) {
            self::SCOPE_LOGIN => $this->security->hitLogin(),
            self::SCOPE_ORDER => $this->security->hitOrder(),
            default => null,
        };
    }

    public function clear(): void
    {
        match ($this->scope) {
            self::SCOPE_LOGIN => $this->security->clearLoginLimit(),
            self::SCOPE_ORDER => $this->security->clearOrderLimit(),
            default => null,
        };
    }
}

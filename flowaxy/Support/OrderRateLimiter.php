<?php

declare(strict_types=1);

namespace Flowaxy\Support;

use Flowaxy\Services\SecurityLogService;

final class OrderRateLimiter
{
    public function __construct(private readonly SecurityLogService $security)
    {
    }

    public function isLimited(): bool
    {
        return $this->security->isOrderLimited();
    }

    public function hit(): void
    {
        $this->security->hitOrder();
    }

    public function clear(): void
    {
        $this->security->clearOrderLimit();
    }
}

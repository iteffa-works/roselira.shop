<?php

declare(strict_types=1);

namespace Flowaxy\Support;

use Flowaxy\Services\SecurityLogService;

final class LoginRateLimiter extends SecurityRateLimiter
{
    public function __construct(SecurityLogService $security)
    {
        parent::__construct($security, self::SCOPE_LOGIN);
    }
}

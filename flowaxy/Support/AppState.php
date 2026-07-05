<?php

declare(strict_types=1);

namespace Flowaxy\Support;

use Flowaxy\Services\LocaleService;

final class AppState
{
    /** @var array<string, mixed> */
    public static array $config = [];

    public static LocaleService $locale;
}

<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Contracts;

interface SettingsRepositoryInterface
{
    public function get(string $key, ?string $default = null): ?string;

    /** @param array<string, string|null> $values */
    public function setMany(array $values): bool;
}

<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Contracts;

interface LocaleRepositoryInterface
{
    /** @return array<string, string> */
    public function loadStrings(string $locale): array;

    /** @param array<string, string> $translations */
    public function saveStrings(string $locale, array $translations): bool;
}

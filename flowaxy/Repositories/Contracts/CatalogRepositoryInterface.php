<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Contracts;

interface CatalogRepositoryInterface
{
    public function load(): array;

    public function save(array $catalog): bool;
}

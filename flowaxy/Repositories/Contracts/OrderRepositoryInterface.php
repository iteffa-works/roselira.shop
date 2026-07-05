<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Contracts;

interface OrderRepositoryInterface
{
  /** @return list<array<string, mixed>> */
    public function all(): array;

    public function findById(string $id): ?array;

    public function save(array $order): bool;

    public function deleteById(string $id): bool;

    /** @param list<string> $statuses */
    public function deleteByStatuses(array $statuses): int;

    public function deleteAll(): int;

    /** @return array<string, int> */
    public function countByStatus(): array;
}

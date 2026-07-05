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

    /**
     * @param 'all'|'within_last'|'older_than' $scope
     * @param list<string>|null $statuses
     */
    public function deleteByPeriod(string $scope, int $periodDays = 0, ?array $statuses = null): int;

    /** @return array<string, int> */
    public function countByStatus(): array;
}

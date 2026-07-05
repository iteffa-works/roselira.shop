<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Contracts;

interface SecurityRepositoryInterface
{
    public function logEvent(
        string $eventType,
        string $verdict,
        string $ip,
        string $userAgent,
        string $path,
        string $method,
        array $meta = [],
    ): void;

    /** @return list<array<string, mixed>> */
    public function listEvents(array $filters = [], int $limit = 100): array;

    /** @return array{total: int, fraud: int, suspect: int, ok: int, rate_limited: int} */
    public function stats(): array;

    public function deleteEventsOlderThan(int $days): int;

    public function deleteAllEvents(): int;

    public function recordRateHit(string $scope, string $ip): void;

    public function countRecentRateHits(string $scope, string $ip, int $windowSeconds): int;

    public function clearRateLimit(string $scope, ?string $ip = null): int;

    public function pruneRateHits(int $windowSeconds): void;
}

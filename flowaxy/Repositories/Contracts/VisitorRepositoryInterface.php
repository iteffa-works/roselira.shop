<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Contracts;

interface VisitorRepositoryInterface
{
    /** @param list<array<string, mixed>> $events */
    public function ingest(string $sessionId, array $sessionMeta, array $events): void;

    /** @return array<string, mixed> */
    public function dashboardSummary(int $days): array;

    /** @return list<array{date: string, sessions: int, pageviews: int}> */
    public function dailyChart(int $days): array;

    /** @return list<array{path: string, views: int, sessions: int}> */
    public function topPages(int $days, int $limit = 8): array;

    /** @return list<array{path: string, clicks: int}> */
    public function topClickPages(int $days, int $limit = 12, ?string $viewport = null): array;

    /** @return list<array{label: string, count: int}> */
    public function breakdown(string $field, int $days, int $limit = 6): array;

    /** @return list<array{label: string, count: int}> */
    public function topReferrers(int $days, int $limit = 6): array;

    /** @return list<array<string, mixed>> */
    public function heatmap(string $path, int $days, string $viewport = 'desktop'): array;

    public function heatmapPreviewWidth(string $path, int $days, string $viewport = 'desktop'): int;

    /** @return array<string, int> */
    public function heatmapViewportCounts(string $path, int $days): array;

    public function guessHeatmapViewport(string $path, int $days): string;

    /** @return list<array<string, mixed>> */
    public function recentSessions(int $limit = 10): array;

    public function purgeOlderThan(int $days): int;
}

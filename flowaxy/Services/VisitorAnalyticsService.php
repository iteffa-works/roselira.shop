<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Core\Request;
use Flowaxy\Repositories\Contracts\VisitorRepositoryInterface;
use Flowaxy\Support\HeatmapViewport;
use Flowaxy\Support\RequestContext;

final class VisitorAnalyticsService
{
    private const MAX_EVENTS_PER_REQUEST = 80;
    private const RETENTION_DAYS = 90;

    public function __construct(
        private readonly VisitorRepositoryInterface $visitors,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function collect(array $payload): bool
    {
        $sessionId = trim((string) ($payload['session_id'] ?? ''));
        if ($sessionId === '' || !preg_match('/^[a-f0-9-]{16,64}$/i', $sessionId)) {
            return false;
        }

        $events = $payload['events'] ?? [];
        if (!is_array($events) || $events === []) {
            return false;
        }

        $events = array_slice($events, 0, self::MAX_EVENTS_PER_REQUEST);
        $normalized = [];

        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $type = (string) ($event['type'] ?? $event['event_type'] ?? '');
            $path = $this->normalizePath((string) ($event['path'] ?? '/'));
            if ($type === '' || str_starts_with($path, '/admin')) {
                continue;
            }

            $row = [
                'event_type' => $type,
                'path' => $path,
                'meta' => is_array($event['meta'] ?? null) ? $event['meta'] : [],
            ];

            if (isset($event['x_pct'])) {
                $row['x_pct'] = $this->clampPct((float) $event['x_pct']);
            }
            if (isset($event['y_pct'])) {
                $row['y_pct'] = $this->clampPct((float) $event['y_pct']);
            }
            if (isset($event['scroll_pct'])) {
                $row['scroll_pct'] = $this->clampPct((float) $event['scroll_pct']);
            }
            if (isset($event['duration_sec'])) {
                $row['duration_sec'] = max(0, min(86400, (int) $event['duration_sec']));
            }
            if (!empty($event['tag'])) {
                $row['meta']['tag'] = mb_substr((string) $event['tag'], 0, 32);
            }
            if ($type === 'click') {
                if (empty($row['meta']['vw'])) {
                    $row['meta']['vw'] = max(0, (int) ($payload['viewport_w'] ?? 0));
                }
                if (empty($row['meta']['vh'])) {
                    $row['meta']['vh'] = max(0, (int) ($payload['viewport_h'] ?? 0));
                }
            }

            $normalized[] = $row;
        }

        if ($normalized === []) {
            return false;
        }

        $ua = RequestContext::userAgent();
        $browser = RequestContext::browserLabel($ua);
        $isBot = $browser === 'Bot' ? 1 : 0;

        $sessionMeta = [
            'ip' => RequestContext::clientIp(),
            'user_agent' => $ua,
            'browser' => $browser,
            'device_type' => $this->deviceType($ua),
            'referrer' => mb_substr((string) ($payload['referrer'] ?? ''), 0, 512),
            'landing_path' => $this->normalizePath((string) ($payload['landing_path'] ?? ($normalized[0]['path'] ?? '/'))),
            'locale' => mb_substr((string) ($payload['locale'] ?? ''), 0, 8),
            'screen_w' => max(0, (int) ($payload['screen_w'] ?? 0)),
            'screen_h' => max(0, (int) ($payload['screen_h'] ?? 0)),
            'viewport_w' => max(0, (int) ($payload['viewport_w'] ?? 0)),
            'viewport_h' => max(0, (int) ($payload['viewport_h'] ?? 0)),
            'is_bot' => $isBot,
        ];

        if ($isBot === 1) {
            return false;
        }

        $this->visitors->ingest($sessionId, $sessionMeta, $normalized);

        return true;
    }

    /** @return array<string, mixed> */
    public function dashboard(int $days = 7): array
    {
        $days = max(1, min(90, $days));
        $topPages = $this->visitors->topPages($days);
        $clickPages = $this->visitors->topClickPages($days);

        return [
            'days' => $days,
            'summary' => $this->visitors->dashboardSummary($days),
            'chart' => $this->visitors->dailyChart($days),
            'top_pages' => $topPages,
            'click_pages' => $clickPages,
            'browsers' => $this->visitors->breakdown('browser', $days),
            'devices' => $this->visitors->breakdown('device_type', $days),
            'locales' => $this->visitors->breakdown('locale', $days),
            'referrers' => $this->visitors->topReferrers($days),
            'recent_sessions' => $this->visitors->recentSessions(12),
        ];
    }

    /** @return array<string, mixed> */
    public function heatmapPage(int $days = 7, string $heatmapPath = '/', string $viewport = ''): array
    {
        $days = max(1, min(90, $days));
        $heatmapPath = $this->normalizePath($heatmapPath);
        $topPages = $this->visitors->topPages($days);

        if ($heatmapPath === '/' && $topPages !== []) {
            $heatmapPath = $this->normalizePath($topPages[0]['path']);
        }

        if ($viewport === '') {
            $viewport = $this->visitors->guessHeatmapViewport($heatmapPath, $days);
        } else {
            $viewport = HeatmapViewport::normalize($viewport);
        }

        $clickPages = $this->visitors->topClickPages($days, 12, $viewport);
        if ($clickPages !== [] && !$this->pathInClickPages($heatmapPath, $clickPages)) {
            $heatmapPath = $this->normalizePath($clickPages[0]['path']);
        }

        $heatmap = $this->visitors->heatmap($heatmapPath, $days, $viewport);
        $profile = HeatmapViewport::profile($viewport);

        return [
            'days' => $days,
            'viewport' => $viewport,
            'viewport_label' => $profile['label'],
            'viewport_preview_w' => $profile['preview'],
            'viewport_counts' => $this->visitors->heatmapViewportCounts($heatmapPath, $days),
            'heatmap_path' => $heatmapPath,
            'heatmap' => $heatmap,
            'heatmap_preview_w' => $this->visitors->heatmapPreviewWidth($heatmapPath, $days, $viewport),
            'heatmap_click_count' => count($heatmap),
            'click_pages' => $clickPages,
            'top_pages' => $topPages,
        ];
    }

    /** @param list<array{path: string, clicks: int}> $clickPages */
    private function pathInClickPages(string $path, array $clickPages): bool
    {
        foreach ($clickPages as $row) {
            if ($this->normalizePath((string) ($row['path'] ?? '')) === $path) {
                return true;
            }
        }

        return false;
    }

    public function purgeOld(): int
    {
        return $this->visitors->purgeOlderThan(self::RETENTION_DAYS);
    }

    /**
     * @param 'all'|'within_last'|'older_than' $scope
     * @param list<string>|null $eventTypes
     * @return array{events: int, sessions: int}
     */
    public function purgeAnalytics(
        string $scope,
        int $periodDays = 0,
        ?string $path = null,
        ?string $viewport = null,
        ?array $eventTypes = null,
    ): array {
        if ($path !== null && $path !== '') {
            $path = $this->normalizePath($path);
        } else {
            $path = null;
        }

        if ($viewport === '') {
            $viewport = null;
        }

        return $this->visitors->purgeAnalytics($scope, $periodDays, $path, $viewport, $eventTypes);
    }

    /**
     * @return array{events: int, sessions: int}|null null when scope is invalid
     */
    public function purgeFromRequest(Request $request): ?array
    {
        $scope = (string) $request->post('scope', '');
        if (!in_array($scope, ['all', 'within_last', 'older_than'], true)) {
            return null;
        }

        $periodDays = max(1, min(3650, (int) $request->post('period_days', 7)));
        $path = $request->post('filter_page') === '1'
            ? (string) $request->post('page', '/')
            : null;
        $viewport = $request->post('filter_viewport') === '1'
            ? (string) $request->post('viewport', '')
            : null;
        $eventTypes = $request->post('clicks_only') === '1' ? ['click'] : null;

        return $this->purgeAnalytics($scope, $periodDays, $path, $viewport, $eventTypes);
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            return '/';
        }

        return mb_substr($path !== '/' ? rtrim($path, '/') ?: '/' : '/', 0, 255);
    }

    private function clampPct(float $value): float
    {
        return max(0.0, min(100.0, round($value, 1)));
    }

    private function deviceType(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'mobile') || str_contains($ua, 'iphone') || str_contains($ua, 'android')) {
            return 'Mobile';
        }
        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'Tablet';
        }

        return 'Desktop';
    }
}

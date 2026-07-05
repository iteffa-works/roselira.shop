<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Sqlite;

use Flowaxy\Repositories\Contracts\VisitorRepositoryInterface;
use Flowaxy\Support\HeatmapViewport;
use Flowaxy\Support\JsonCodec;

final class SqliteVisitorRepository implements VisitorRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /** @param array<string, mixed> $sessionMeta @param list<array<string, mixed>> $events */
    public function ingest(string $sessionId, array $sessionMeta, array $events): void
    {
        if ($sessionId === '' || $events === []) {
            return;
        }

        $pdo = $this->connection->pdo();
        $now = date('c');

        $pdo->beginTransaction();

        try {
            $existing = $pdo->prepare('SELECT id FROM visitor_sessions WHERE id = :id');
            $existing->execute(['id' => $sessionId]);
            $found = $existing->fetchColumn();

            $pageViewsDelta = 0;
            $eventsDelta = count($events);
            $durationDelta = 0;

            foreach ($events as $event) {
                if (($event['event_type'] ?? '') === 'pageview') {
                    $pageViewsDelta++;
                }
                if (($event['event_type'] ?? '') === 'leave') {
                    $durationDelta = max($durationDelta, (int) ($event['duration_sec'] ?? 0));
                }
            }

            if ($found === false) {
                $insert = $pdo->prepare(<<<'SQL'
                    INSERT INTO visitor_sessions (
                        id, created_at, last_seen_at, ip, user_agent, browser, device_type,
                        referrer, landing_path, locale, screen_w, screen_h, viewport_w, viewport_h,
                        page_views, events_count, duration_sec, is_bot
                    ) VALUES (
                        :id, :created_at, :last_seen_at, :ip, :user_agent, :browser, :device_type,
                        :referrer, :landing_path, :locale, :screen_w, :screen_h, :viewport_w, :viewport_h,
                        :page_views, :events_count, :duration_sec, :is_bot
                    )
                    SQL);
                $insert->execute([
                    'id' => $sessionId,
                    'created_at' => $now,
                    'last_seen_at' => $now,
                    'ip' => (string) ($sessionMeta['ip'] ?? ''),
                    'user_agent' => (string) ($sessionMeta['user_agent'] ?? ''),
                    'browser' => (string) ($sessionMeta['browser'] ?? ''),
                    'device_type' => (string) ($sessionMeta['device_type'] ?? ''),
                    'referrer' => mb_substr((string) ($sessionMeta['referrer'] ?? ''), 0, 512),
                    'landing_path' => (string) ($sessionMeta['landing_path'] ?? '/'),
                    'locale' => (string) ($sessionMeta['locale'] ?? ''),
                    'screen_w' => (int) ($sessionMeta['screen_w'] ?? 0),
                    'screen_h' => (int) ($sessionMeta['screen_h'] ?? 0),
                    'viewport_w' => (int) ($sessionMeta['viewport_w'] ?? 0),
                    'viewport_h' => (int) ($sessionMeta['viewport_h'] ?? 0),
                    'page_views' => $pageViewsDelta,
                    'events_count' => $eventsDelta,
                    'duration_sec' => $durationDelta,
                    'is_bot' => (int) ($sessionMeta['is_bot'] ?? 0),
                ]);
            } else {
                $update = $pdo->prepare(<<<'SQL'
                    UPDATE visitor_sessions SET
                        last_seen_at = :last_seen_at,
                        page_views = page_views + :page_views,
                        events_count = events_count + :events_count,
                        duration_sec = MAX(duration_sec, :duration_sec),
                        viewport_w = CASE WHEN :viewport_w > 0 THEN :viewport_w ELSE viewport_w END,
                        viewport_h = CASE WHEN :viewport_h > 0 THEN :viewport_h ELSE viewport_h END
                    WHERE id = :id
                    SQL);
                $update->execute([
                    'id' => $sessionId,
                    'last_seen_at' => $now,
                    'page_views' => $pageViewsDelta,
                    'events_count' => $eventsDelta,
                    'duration_sec' => $durationDelta,
                    'viewport_w' => (int) ($sessionMeta['viewport_w'] ?? 0),
                    'viewport_h' => (int) ($sessionMeta['viewport_h'] ?? 0),
                ]);
            }

            $eventStmt = $pdo->prepare(<<<'SQL'
                INSERT INTO visitor_events (
                    session_id, created_at, event_type, path, x_pct, y_pct, scroll_pct, meta
                ) VALUES (
                    :session_id, :created_at, :event_type, :path, :x_pct, :y_pct, :scroll_pct, :meta
                )
                SQL);

            foreach ($events as $event) {
                $eventStmt->execute([
                    'session_id' => $sessionId,
                    'created_at' => $now,
                    'event_type' => (string) ($event['event_type'] ?? 'unknown'),
                    'path' => mb_substr((string) ($event['path'] ?? '/'), 0, 255),
                    'x_pct' => isset($event['x_pct']) ? (float) $event['x_pct'] : null,
                    'y_pct' => isset($event['y_pct']) ? (float) $event['y_pct'] : null,
                    'scroll_pct' => isset($event['scroll_pct']) ? (float) $event['scroll_pct'] : null,
                    'meta' => JsonCodec::encode($event['meta'] ?? []),
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @return array<string, mixed> */
    public function dashboardSummary(int $days): array
    {
        $since = $this->since($days);
        $pdo = $this->connection->pdo();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM visitor_sessions WHERE is_bot = 0 AND created_at >= :since');
        $stmt->execute(['since' => $since]);
        $sessions = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(page_views), 0) FROM visitor_sessions WHERE is_bot = 0 AND created_at >= :since');
        $stmt->execute(['since' => $since]);
        $pageViews = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT AVG(duration_sec) FROM visitor_sessions WHERE is_bot = 0 AND created_at >= :since AND duration_sec > 0');
        $stmt->execute(['since' => $since]);
        $avgDuration = (float) ($stmt->fetchColumn() ?: 0);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM visitor_sessions WHERE is_bot = 0 AND created_at >= :since AND page_views <= 1');
        $stmt->execute(['since' => $since]);
        $bounces = (int) $stmt->fetchColumn();

        $bounceRate = $sessions > 0 ? round(($bounces / $sessions) * 100, 1) : 0.0;

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM visitor_events e INNER JOIN visitor_sessions s ON s.id = e.session_id WHERE s.is_bot = 0 AND e.created_at >= :since AND e.event_type = :type');
        $stmt->execute(['since' => $since, 'type' => 'click']);
        $clicks = (int) $stmt->fetchColumn();

        return [
            'sessions' => $sessions,
            'page_views' => $pageViews,
            'avg_duration_sec' => (int) round($avgDuration),
            'bounce_rate' => $bounceRate,
            'clicks' => $clicks,
        ];
    }

    /** @return list<array{date: string, sessions: int, pageviews: int}> */
    public function dailyChart(int $days): array
    {
        $since = $this->since($days);
        $pdo = $this->connection->pdo();

        $stmt = $pdo->prepare(<<<'SQL'
            SELECT substr(created_at, 1, 10) AS day,
                   COUNT(*) AS sessions,
                   COALESCE(SUM(page_views), 0) AS pageviews
            FROM visitor_sessions
            WHERE is_bot = 0 AND created_at >= :since
            GROUP BY day
            ORDER BY day ASC
            SQL);
        $stmt->execute(['since' => $since]);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'date' => (string) $row['day'],
                'sessions' => (int) $row['sessions'],
                'pageviews' => (int) $row['pageviews'],
            ];
        }

        return $this->fillDailyGaps($rows, $days);
    }

    /** @return list<array{path: string, views: int, sessions: int}> */
    public function topPages(int $days, int $limit = 8): array
    {
        $since = $this->since($days);
        $pdo = $this->connection->pdo();

        $stmt = $pdo->prepare(<<<'SQL'
            SELECT path,
                   COUNT(*) AS views,
                   COUNT(DISTINCT session_id) AS sessions
            FROM visitor_events
            WHERE event_type = 'pageview' AND created_at >= :since
            GROUP BY path
            ORDER BY views DESC
            LIMIT :limit
            SQL);
        $stmt->bindValue('since', $since);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'path' => (string) $row['path'],
                'views' => (int) $row['views'],
                'sessions' => (int) $row['sessions'],
            ];
        }

        return $rows;
    }

    /** @return list<array{path: string, clicks: int}> */
    public function topClickPages(int $days, int $limit = 12, ?string $viewport = null): array
    {
        $since = $this->since($days);
        $pdo = $this->connection->pdo();
        $vwExpr = $this->effectiveViewportWidthExpression();
        $viewportFilter = $this->viewportSqlFilter($viewport, $vwExpr);

        $stmt = $pdo->prepare(<<<SQL
            SELECT e.path,
                   COUNT(*) AS clicks
            FROM visitor_events e
            INNER JOIN visitor_sessions s ON s.id = e.session_id
            WHERE e.event_type = 'click'
              AND s.is_bot = 0
              AND e.created_at >= :since
              AND e.x_pct IS NOT NULL
              AND e.y_pct IS NOT NULL
              {$viewportFilter}
            GROUP BY e.path
            ORDER BY clicks DESC
            LIMIT :limit
            SQL);
        $stmt->bindValue('since', $since);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $this->bindViewportRange($stmt, $viewport);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'path' => (string) $row['path'],
                'clicks' => (int) $row['clicks'],
            ];
        }

        return $rows;
    }

    /** @return list<array{label: string, count: int}> */
    public function breakdown(string $field, int $days, int $limit = 6): array
    {
        $allowed = ['browser', 'device_type', 'locale'];
        if (!in_array($field, $allowed, true)) {
            return [];
        }

        $since = $this->since($days);
        $pdo = $this->connection->pdo();

        $sql = sprintf(
            'SELECT %s AS label, COUNT(*) AS count FROM visitor_sessions WHERE is_bot = 0 AND created_at >= :since GROUP BY %s ORDER BY count DESC LIMIT :limit',
            $field,
            $field,
        );
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue('since', $since);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                $label = '—';
            }
            $rows[] = ['label' => $label, 'count' => (int) $row['count']];
        }

        return $rows;
    }

    /** @return list<array{label: string, count: int}> */
    public function topReferrers(int $days, int $limit = 6): array
    {
        $since = $this->since($days);
        $pdo = $this->connection->pdo();

        $stmt = $pdo->prepare(<<<'SQL'
            SELECT referrer AS label, COUNT(*) AS count
            FROM visitor_sessions
            WHERE is_bot = 0 AND created_at >= :since AND referrer != ''
            GROUP BY referrer
            ORDER BY count DESC
            LIMIT :limit
            SQL);
        $stmt->bindValue('since', $since);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'label' => $this->referrerLabel((string) ($row['label'] ?? '')),
                'count' => (int) $row['count'],
            ];
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public function heatmap(string $path, int $days, string $viewport = 'desktop'): array
    {
        $since = $this->since($days);
        $pdo = $this->connection->pdo();
        $vwExpr = $this->effectiveViewportWidthExpression();
        $viewportFilter = $this->viewportSqlFilter($viewport, $vwExpr);

        $stmt = $pdo->prepare(<<<SQL
            SELECT e.x_pct, e.y_pct, e.meta
            FROM visitor_events e
            INNER JOIN visitor_sessions s ON s.id = e.session_id
            WHERE e.event_type = 'click'
              AND s.is_bot = 0
              AND e.path = :path
              AND e.created_at >= :since
              AND e.x_pct IS NOT NULL
              AND e.y_pct IS NOT NULL
              {$viewportFilter}
            ORDER BY e.created_at DESC
            LIMIT 300
            SQL);
        $stmt->bindValue('path', $path);
        $stmt->bindValue('since', $since);
        $this->bindViewportRange($stmt, $viewport);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $meta = JsonCodec::decode((string) ($row['meta'] ?? '{}'));
            $rows[] = [
                'x_pct' => (float) $row['x_pct'],
                'y_pct' => (float) $row['y_pct'],
                'weight' => 1,
                'href' => (string) ($meta['href'] ?? ''),
                'page_x' => (float) ($meta['page_x'] ?? 0),
                'page_y' => (float) ($meta['page_y'] ?? 0),
                'doc_w' => (float) ($meta['doc_w'] ?? 0),
                'doc_h' => (float) ($meta['doc_h'] ?? 0),
                'vw' => (int) ($meta['vw'] ?? 0),
            ];
        }

        return $rows;
    }

    public function heatmapPreviewWidth(string $path, int $days, string $viewport = 'desktop'): int
    {
        return HeatmapViewport::profile($viewport)['preview'];
    }

    public function heatmapClickCount(string $path, int $days, string $viewport = 'desktop'): int
    {
        $since = $this->since($days);
        $pdo = $this->connection->pdo();
        $vwExpr = $this->effectiveViewportWidthExpression();
        $viewportFilter = $this->viewportSqlFilter($viewport, $vwExpr);

        $stmt = $pdo->prepare(<<<SQL
            SELECT COUNT(*)
            FROM visitor_events e
            INNER JOIN visitor_sessions s ON s.id = e.session_id
            WHERE e.event_type = 'click'
              AND s.is_bot = 0
              AND e.path = :path
              AND e.created_at >= :since
              AND e.x_pct IS NOT NULL
              AND e.y_pct IS NOT NULL
              {$viewportFilter}
            SQL);
        $stmt->execute(['path' => $path, 'since' => $since] + $this->viewportRangeParams($viewport));

        return (int) $stmt->fetchColumn();
    }

    /** @return array<string, int> */
    public function heatmapViewportCounts(string $path, int $days): array
    {
        $since = $this->since($days);
        $pdo = $this->connection->pdo();
        $vwExpr = $this->effectiveViewportWidthExpression();
        $counts = [];

        foreach (HeatmapViewport::ids() as $profileId) {
            $profile = HeatmapViewport::profile($profileId);
            $stmt = $pdo->prepare(<<<SQL
                SELECT COUNT(*)
                FROM visitor_events e
                INNER JOIN visitor_sessions s ON s.id = e.session_id
                WHERE e.event_type = 'click'
                  AND s.is_bot = 0
                  AND e.path = :path
                  AND e.created_at >= :since
                  AND e.x_pct IS NOT NULL
                  AND e.y_pct IS NOT NULL
                  AND {$vwExpr} >= :min_vw
                  AND {$vwExpr} <= :max_vw
                SQL);
            $stmt->bindValue('path', $path);
            $stmt->bindValue('since', $since);
            $stmt->bindValue('min_vw', $profile['min'], \PDO::PARAM_INT);
            $stmt->bindValue('max_vw', $profile['max'], \PDO::PARAM_INT);
            $stmt->execute();
            $counts[$profileId] = (int) $stmt->fetchColumn();
        }

        return $counts;
    }

    public function guessHeatmapViewport(string $path, int $days): string
    {
        $counts = $this->heatmapViewportCounts($path, $days);
        arsort($counts);
        foreach ($counts as $profileId => $count) {
            if ($count > 0) {
                return (string) $profileId;
            }
        }

        return 'desktop';
    }

    /** @return list<array<string, mixed>> */
    public function recentSessions(int $limit = 10): array
    {
        $pdo = $this->connection->pdo();
        $stmt = $pdo->prepare(<<<'SQL'
            SELECT id, created_at, last_seen_at, ip, browser, device_type, referrer, landing_path,
                   locale, page_views, duration_sec, viewport_w, viewport_h
            FROM visitor_sessions
            WHERE is_bot = 0
            ORDER BY last_seen_at DESC
            LIMIT :limit
            SQL);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn(array $row): array => $row, $stmt->fetchAll());
    }

    public function purgeOlderThan(int $days): int
    {
        $since = date('c', strtotime('-' . max(1, $days) . ' days'));
        $pdo = $this->connection->pdo();

        $pdo->prepare('DELETE FROM visitor_events WHERE created_at < :since')->execute(['since' => $since]);
        $stmt = $pdo->prepare('DELETE FROM visitor_sessions WHERE created_at < :since');
        $stmt->execute(['since' => $since]);

        return $stmt->rowCount();
    }

    private function since(int $days): string
    {
        $days = max(1, min(90, $days));

        return date('c', strtotime('-' . $days . ' days'));
    }

    /** @param list<array{date: string, sessions: int, pageviews: int}> $rows */
    private function fillDailyGaps(array $rows, int $days): array
    {
        $map = [];
        foreach ($rows as $row) {
            $map[$row['date']] = $row;
        }

        $filled = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $filled[] = $map[$date] ?? ['date' => $date, 'sessions' => 0, 'pageviews' => 0];
        }

        return $filled;
    }

    private function referrerLabel(string $referrer): string
    {
        if ($referrer === '') {
            return 'Прямий';
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : mb_substr($referrer, 0, 48);
    }

    private function effectiveViewportWidthExpression(string $eventAlias = 'e', string $sessionAlias = 's'): string
    {
        $metaVw = "NULLIF(CAST(json_extract({$eventAlias}.meta, '$.vw') AS INTEGER), 0)";
        $sessionVw = "NULLIF({$sessionAlias}.viewport_w, 0)";

        return "COALESCE({$metaVw}, {$sessionVw}, CASE {$sessionAlias}.device_type WHEN 'Mobile' THEN 390 WHEN 'Tablet' THEN 768 ELSE 1280 END)";
    }

    private function viewportSqlFilter(?string $viewport, string $vwExpr): string
    {
        if ($viewport === null || $viewport === '') {
            return '';
        }

        return " AND {$vwExpr} >= :min_vw AND {$vwExpr} <= :max_vw";
    }

    /** @return array<string, int> */
    private function viewportRangeParams(?string $viewport): array
    {
        if ($viewport === null || $viewport === '') {
            return [];
        }

        $profile = HeatmapViewport::profile($viewport);

        return [
            'min_vw' => $profile['min'],
            'max_vw' => $profile['max'],
        ];
    }

    private function bindViewportRange(\PDOStatement $stmt, ?string $viewport): void
    {
        foreach ($this->viewportRangeParams($viewport) as $key => $value) {
            $stmt->bindValue($key, $value, \PDO::PARAM_INT);
        }
    }
}

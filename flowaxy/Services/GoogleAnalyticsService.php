<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Support\GoogleServiceAccount;

final class GoogleAnalyticsService
{
    public function __construct(
        private readonly string $propertyId,
        private readonly string $serviceAccountPath,
        private readonly string $lookerEmbedUrl,
        private readonly string $measurementId,
        private readonly string $gtmId,
        private readonly string $projectRoot,
    ) {
    }

    public function canShowGoogleTab(): bool
    {
        return $this->hasApiAccess() || $this->lookerEmbedUrl !== '' || $this->measurementId !== '' || $this->gtmId !== '';
    }

    public function hasApiAccess(): bool
    {
        return $this->propertyId !== '' && $this->resolvedServiceAccountPath() !== null;
    }

    /** @return array<string, mixed> */
    public function dashboard(int $days = 7): array
    {
        $days = max(1, min(90, $days));
        $base = [
            'days' => $days,
            'measurement_id' => $this->measurementId,
            'gtm_id' => $this->gtmId,
            'property_id' => $this->propertyId,
            'error' => null,
        ];

        if ($this->lookerEmbedUrl !== '') {
            return array_merge($base, [
                'mode' => 'embed',
                'embed_url' => $this->lookerEmbedUrl,
            ]);
        }

        if (!$this->hasApiAccess()) {
            return array_merge($base, ['mode' => 'links']);
        }

        [$reports, $error] = $days === 1 ? $this->fetchRealtimeReports() : $this->fetchReports($days);
        if ($reports === null) {
            return array_merge($base, [
                'mode' => 'links',
                'error' => $error ?? 'Не вдалося отримати дані GA4. Перевірте Property ID, service account і права Viewer у GA4.',
            ]);
        }

        if ($days === 1) {
            $reports['realtime'] = true;
        } else {
            $token = $this->resolveAccessToken();
            if ($token !== null) {
                $reports['live'] = $this->fetchLiveSnapshot($token);
            }
        }

        return array_merge($base, $reports, ['mode' => 'api']);
    }

    /** @return array{active_users: int, event_count: int}|null */
    public function liveSnapshot(): ?array
    {
        $token = $this->resolveAccessToken();

        return $token !== null ? $this->fetchLiveSnapshot($token) : null;
    }

    /** @return array{active_users: int, event_count: int} */
    private function fetchLiveSnapshot(string $token): array
    {
        [$response] = $this->runRealtimeReport([
            'metrics' => [
                ['name' => 'activeUsers'],
                ['name' => 'eventCount'],
            ],
        ], $token);

        $row = $this->metricValues($response ?? [])[0] ?? [];

        return [
            'active_users' => (int) ($row[0] ?? 0),
            'event_count' => (int) ($row[1] ?? 0),
        ];
    }

    private function resolveAccessToken(): ?string
    {
        $path = $this->resolvedServiceAccountPath();
        if ($path === null) {
            return null;
        }

        return GoogleServiceAccount::accessToken($path);
    }

    /** @return array{0: array<string, mixed>|null, 1: string|null} */
    private function fetchRealtimeReports(): array
    {
        $path = $this->resolvedServiceAccountPath();
        if ($path === null) {
            return [null, 'JSON service account не знайдено або PHP не може його прочитати (перевірте шлях і chmod 644).'];
        }

        $token = GoogleServiceAccount::accessToken($path);
        if ($token === null) {
            return [null, 'OAuth-токен не отримано — ключ JSON недійсний або відкликаний у Google Cloud.'];
        }

        [$summaryResp, $summaryError] = $this->runRealtimeReport([
            'metrics' => [
                ['name' => 'activeUsers'],
                ['name' => 'eventCount'],
            ],
        ], $token);
        if ($summaryResp === null) {
            return [null, $summaryError ?? 'GA4 Realtime API не відповіла.'];
        }

        [$chartResp] = $this->runRealtimeReport([
            'dimensions' => [['name' => 'minutesAgo']],
            'metrics' => [['name' => 'activeUsers']],
            'orderBys' => [['dimension' => ['dimensionName' => 'minutesAgo'], 'desc' => true]],
        ], $token);

        [$pagesResp] = $this->runRealtimeReport([
            'dimensions' => [['name' => 'unifiedScreenName']],
            'metrics' => [['name' => 'activeUsers']],
            'orderBys' => [['metric' => ['metricName' => 'activeUsers'], 'desc' => true]],
            'limit' => 10,
        ], $token);

        [$devicesResp] = $this->runRealtimeReport([
            'dimensions' => [['name' => 'deviceCategory']],
            'metrics' => [['name' => 'activeUsers']],
            'orderBys' => [['metric' => ['metricName' => 'activeUsers'], 'desc' => true]],
            'limit' => 8,
        ], $token);

        [$countriesResp] = $this->runRealtimeReport([
            'dimensions' => [['name' => 'country']],
            'metrics' => [['name' => 'activeUsers']],
            'orderBys' => [['metric' => ['metricName' => 'activeUsers'], 'desc' => true]],
            'limit' => 8,
        ], $token);

        $summaryRow = $this->metricValues($summaryResp)[0] ?? [];

        return [[
            'summary' => [
                'sessions' => (int) ($summaryRow[0] ?? 0),
                'page_views' => (int) ($summaryRow[1] ?? 0),
                'avg_duration_sec' => 0,
                'bounce_rate' => 0.0,
                'active_users' => (int) ($summaryRow[0] ?? 0),
            ],
            'chart' => $this->chartFromGaRows($chartResp ?? [], static function (string $minutesAgo): string {
                return $minutesAgo === '0' ? 'зараз' : $minutesAgo . ' хв';
            }, includePageviews: false),
            'top_pages' => $this->dimensionReport($pagesResp ?? [], 'unifiedScreenName', 'activeUsers'),
            'devices' => $this->dimensionReport($devicesResp ?? [], 'deviceCategory', 'activeUsers'),
            'sources' => $this->dimensionReport($countriesResp ?? [], 'country', 'activeUsers'),
        ], null];
    }

    /** @param array<string, mixed> $body
     * @return array{0: array<string, mixed>|null, 1: string|null}
     */
    private function runRealtimeReport(array $body, string $token): array
    {
        $url = 'https://analyticsdata.googleapis.com/v1beta/properties/' . rawurlencode($this->propertyId) . ':runRealtimeReport';

        return $this->postJson($url, $body, $token);
    }

    /** @param callable(string): string $labelForDimension
     * @return list<array{date: string, sessions: int, pageviews: int}>
     */
    private function chartFromGaRows(array $report, callable $labelForDimension, bool $includePageviews = true): array
    {
        $rows = $report['rows'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $chart = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $dimension = (string) ($row['dimensionValues'][0]['value'] ?? '');
            if ($dimension === '') {
                continue;
            }
            $metrics = $row['metricValues'] ?? [];
            $chart[] = [
                'date' => $labelForDimension($dimension),
                'sessions' => (int) ($metrics[0]['value'] ?? 0),
                'pageviews' => $includePageviews ? (int) ($metrics[1]['value'] ?? 0) : 0,
            ];
        }

        return $chart;
    }

    /** @return array{0: array<string, mixed>|null, 1: string|null} */
    private function fetchReports(int $days): array
    {
        $path = $this->resolvedServiceAccountPath();
        if ($path === null) {
            return [null, 'JSON service account не знайдено або PHP не може його прочитати (перевірте шлях і chmod 644).'];
        }

        $token = GoogleServiceAccount::accessToken($path);
        if ($token === null) {
            return [null, 'OAuth-токен не отримано — ключ JSON недійсний або відкликаний у Google Cloud.'];
        }

        $range = [
            'startDate' => $days === 1 ? 'today' : $days . 'daysAgo',
            'endDate' => 'today',
        ];

        $payload = [
            'requests' => [
                [
                    'dateRanges' => [$range],
                    'metrics' => [
                        ['name' => 'sessions'],
                        ['name' => 'screenPageViews'],
                        ['name' => 'averageSessionDuration'],
                        ['name' => 'bounceRate'],
                        ['name' => 'activeUsers'],
                    ],
                ],
                [
                    'dateRanges' => [$range],
                    'dimensions' => [['name' => 'date']],
                    'metrics' => [
                        ['name' => 'sessions'],
                        ['name' => 'screenPageViews'],
                    ],
                    'orderBys' => [['dimension' => ['dimensionName' => 'date']]],
                ],
                [
                    'dateRanges' => [$range],
                    'dimensions' => [['name' => 'pagePath']],
                    'metrics' => [['name' => 'screenPageViews']],
                    'orderBys' => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
                    'limit' => 10,
                ],
                [
                    'dateRanges' => [$range],
                    'dimensions' => [['name' => 'deviceCategory']],
                    'metrics' => [['name' => 'sessions']],
                    'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
                    'limit' => 8,
                ],
                [
                    'dateRanges' => [$range],
                    'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
                    'metrics' => [['name' => 'sessions']],
                    'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
                    'limit' => 8,
                ],
            ],
        ];

        $url = 'https://analyticsdata.googleapis.com/v1beta/properties/' . rawurlencode($this->propertyId) . ':batchRunReports';
        [$response, $apiError] = $this->postJson($url, $payload, $token);
        if ($response === null || !isset($response['reports']) || !is_array($response['reports'])) {
            return [null, $apiError ?? 'GA4 Data API не відповіла. Перевірте allow_url_fopen і доступ до googleapis.com.'];
        }

        $reports = $response['reports'];
        $summaryRow = $this->metricValues($reports[0] ?? [])[0] ?? [];

        return [[
            'summary' => [
                'sessions' => (int) ($summaryRow[0] ?? 0),
                'page_views' => (int) ($summaryRow[1] ?? 0),
                'avg_duration_sec' => (int) round((float) ($summaryRow[2] ?? 0)),
                'bounce_rate' => round((float) ($summaryRow[3] ?? 0) * 100, 1),
                'active_users' => (int) ($summaryRow[4] ?? 0),
            ],
            'chart' => $this->chartFromGaRows($reports[1] ?? [], static function (string $date): string {
                if (strlen($date) !== 8) {
                    return $date;
                }

                return substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
            }),
            'top_pages' => $this->dimensionReport($reports[2] ?? [], 'pagePath', 'screenPageViews'),
            'devices' => $this->dimensionReport($reports[3] ?? [], 'deviceCategory', 'sessions'),
            'sources' => $this->dimensionReport($reports[4] ?? [], 'sessionDefaultChannelGroup', 'sessions'),
        ], null];
    }

    /** @return list<array{label: string, count: int}> */
    private function dimensionReport(array $report, string $dimension, string $metric): array
    {
        $rows = $report['rows'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = (string) ($row['dimensionValues'][0]['value'] ?? '');
            $count = (int) ($row['metricValues'][0]['value'] ?? 0);
            if ($label === '' || $count <= 0) {
                continue;
            }
            $items[] = ['label' => $label, 'count' => $count];
        }

        return $items;
    }

    /** @return list<list<string>> */
    private function metricValues(array $report): array
    {
        $rows = $report['rows'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $values = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $line = [];
            foreach ($row['metricValues'] ?? [] as $metric) {
                $line[] = (string) ($metric['value'] ?? '0');
            }
            $values[] = $line;
        }

        if ($values !== []) {
            return $values;
        }

        foreach ($report['totals'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $line = [];
            foreach ($row['metricValues'] ?? [] as $metric) {
                $line[] = (string) ($metric['value'] ?? '0');
            }
            if ($line !== []) {
                $values[] = $line;
            }
        }

        return $values;
    }

    /** @param array<string, mixed> $payload
     * @return array{0: array<string, mixed>|null, 1: string|null}
     */
    private function postJson(string $url, array $payload, string $token): array
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer {$token}\r\nContent-Type: application/json\r\n",
                'content' => $body,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return [null, null];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [null, null];
        }

        if (isset($decoded['error']) && is_array($decoded['error'])) {
            $message = trim((string) ($decoded['error']['message'] ?? ''));
            $status = trim((string) ($decoded['error']['status'] ?? ''));

            return [null, $message !== ''
                ? ($status !== '' ? "{$status}: {$message}" : $message)
                : 'GA4 API повернула помилку.'];
        }

        return [$decoded, null];
    }

    private function resolvedServiceAccountPath(): ?string
    {
        $path = trim($this->serviceAccountPath);
        if ($path === '') {
            return null;
        }

        if (!str_starts_with($path, '/') && !preg_match('/^[A-Za-z]:\\\\/', $path)) {
            $path = rtrim($this->projectRoot, '/\\') . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        }

        return is_readable($path) ? $path : null;
    }
}

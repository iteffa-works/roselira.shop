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

        $reports = $this->fetchReports($days);
        if ($reports === null) {
            return array_merge($base, [
                'mode' => 'links',
                'error' => 'Не вдалося отримати дані GA4. Перевірте Property ID, service account і права Viewer у GA4.',
            ]);
        }

        return array_merge($base, $reports, ['mode' => 'api']);
    }

    /** @return array<string, mixed>|null */
    private function fetchReports(int $days): ?array
    {
        $path = $this->resolvedServiceAccountPath();
        if ($path === null) {
            return null;
        }

        $token = GoogleServiceAccount::accessToken($path);
        if ($token === null) {
            return null;
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
        $response = $this->postJson($url, $payload, $token);
        if ($response === null || !isset($response['reports']) || !is_array($response['reports'])) {
            return null;
        }

        $reports = $response['reports'];
        $summaryRow = $this->metricValues($reports[0] ?? [])[0] ?? [];

        return [
            'summary' => [
                'sessions' => (int) ($summaryRow[0] ?? 0),
                'page_views' => (int) ($summaryRow[1] ?? 0),
                'avg_duration_sec' => (int) round((float) ($summaryRow[2] ?? 0)),
                'bounce_rate' => round((float) ($summaryRow[3] ?? 0) * 100, 1),
                'active_users' => (int) ($summaryRow[4] ?? 0),
            ],
            'chart' => $this->chartFromReport($reports[1] ?? []),
            'top_pages' => $this->dimensionReport($reports[2] ?? [], 'pagePath', 'screenPageViews'),
            'devices' => $this->dimensionReport($reports[3] ?? [], 'deviceCategory', 'sessions'),
            'sources' => $this->dimensionReport($reports[4] ?? [], 'sessionDefaultChannelGroup', 'sessions'),
        ];
    }

    /** @return list<array{date: string, sessions: int, pageviews: int}> */
    private function chartFromReport(array $report): array
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
            $date = (string) ($row['dimensionValues'][0]['value'] ?? '');
            if ($date === '') {
                continue;
            }
            $formatted = strlen($date) === 8
                ? substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2)
                : $date;
            $metrics = $row['metricValues'] ?? [];
            $chart[] = [
                'date' => $formatted,
                'sessions' => (int) ($metrics[0]['value'] ?? 0),
                'pageviews' => (int) ($metrics[1]['value'] ?? 0),
            ];
        }

        return $chart;
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

        return $values;
    }

    /** @param array<string, mixed> $payload */
    private function postJson(string $url, array $payload, string $token): ?array
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
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
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

<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Repositories\Contracts\SettingsRepositoryInterface;
use Flowaxy\Support\CronInterval;
use Flowaxy\Support\Logger;

final class CronService
{
    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_CLI = 'cli';
    public const SOURCE_HTTP = 'http';

    private const KEY_LAST_AT = 'cron_last_run_at';
    private const KEY_LAST_STATUS = 'cron_last_run_status';
    private const KEY_LAST_OUTPUT = 'cron_last_run_output';

    private const SCHEDULE_HOUR = 4;
    private const HOSTING_STALE_SECONDS = 90000;

    /** @var array<string, array{at: string, status: string}> */
    private const SOURCE_KEYS = [
        self::SOURCE_ADMIN => ['cron_last_admin_at', 'cron_last_admin_status'],
        self::SOURCE_CLI => ['cron_last_cli_at', 'cron_last_cli_status'],
        self::SOURCE_HTTP => ['cron_last_http_at', 'cron_last_http_status'],
    ];

    public function __construct(
        private readonly GitUpdateService $gitUpdate,
        private readonly SystemCheckService $systemCheck,
        private readonly SeoFilesService $seoFiles,
        private readonly SettingsRepositoryInterface $settings,
        private readonly VisitorAnalyticsService $visitorAnalytics,
    ) {
    }

    /** @return array{success: bool, message: string, output: string, skipped?: bool, checks?: array<string, mixed>} */
    public function runDaily(bool $forceDaily = true, string $source = self::SOURCE_CLI): array
    {
        $lines = [];
        $hasError = false;

        if ($forceDaily && !$this->gitUpdate->shouldRunDaily() && $this->wasRunRecently()) {
            $this->record($source, 'skipped', 'Cron вже виконувався сьогодні.');

            return [
                'success' => true,
                'message' => 'Cron вже виконувався сьогодні.',
                'output' => '',
                'skipped' => true,
            ];
        }

        $lines[] = '=== Git update ===';
        $git = $this->gitUpdate->pull(forceDaily: false);
        $lines[] = ($git['success'] ? 'OK' : 'ERROR') . ': ' . $git['message'];
        if (!empty($git['output'])) {
            $lines[] = $git['output'];
        }
        if (!$git['success'] && empty($git['skipped'])) {
            $hasError = true;
        }

        $lines[] = '';
        $lines[] = '=== System checks ===';
        $checks = $this->systemCheck->runAll();
        $summary = $checks['summary'];
        $lines[] = sprintf('OK: %d, WARN: %d, ERROR: %d', $summary['ok'], $summary['warn'], $summary['error']);

        foreach ($checks['items'] as $item) {
            $lines[] = strtoupper((string) $item['status']) . ' — ' . $item['label'] . ': ' . $item['message'];
            if (($item['status'] ?? '') === 'error') {
                $hasError = true;
            }
        }

        $lines[] = '';
        $lines[] = '=== SEO files ===';
        $seo = $this->seoFiles->sync();
        $lines[] = ($seo['success'] ? 'OK' : 'ERROR') . ': ' . $seo['message'];
        if (!$seo['success']) {
            $hasError = true;
        }

        $lines[] = '';
        $lines[] = '=== Analytics retention ===';
        $purged = $this->visitorAnalytics->purgeOld();
        $lines[] = 'Purged sessions older than retention: ' . $purged;

        $output = implode("\n", $lines);
        $message = $hasError ? 'Cron завершено з помилками.' : 'Cron виконано успішно.';
        $this->record($source, $hasError ? 'error' : 'ok', $message, $output);

        if ($hasError) {
            Logger::error('Cron completed with errors', ['source' => $source, 'output' => $output]);
        } else {
            Logger::info('Cron completed successfully', ['source' => $source]);
        }

        return [
            'success' => !$hasError,
            'message' => $message,
            'output' => $output,
            'checks' => $checks,
        ];
    }

    /** @return array<string, mixed> */
    public function getCronStatus(): array
    {
        $admin = $this->sourceStatus(self::SOURCE_ADMIN);
        $cli = $this->sourceStatus(self::SOURCE_CLI);
        $http = $this->sourceStatus(self::SOURCE_HTTP);
        $hosting = $this->latestHostingRun($cli, $http);

        return [
            'last_run_at' => $this->settings->get(self::KEY_LAST_AT),
            'last_status' => $this->settings->get(self::KEY_LAST_STATUS),
            'last_output' => $this->settings->get(self::KEY_LAST_OUTPUT),
            'admin' => $admin,
            'cli' => $cli,
            'http' => $http,
            'hosting' => $hosting,
            'hosting_stale' => $this->isHostingStale($hosting['at'] ?? null),
            'next_scheduled_at' => $this->nextScheduledAt(),
            'schedule_label' => sprintf('Щодня о %02d:00', self::SCHEDULE_HOUR),
        ];
    }

    /** @return array{at: ?string, status: ?string} */
    private function sourceStatus(string $source): array
    {
        [$atKey, $statusKey] = self::SOURCE_KEYS[$source];

        return [
            'at' => $this->settings->get($atKey),
            'status' => $this->settings->get($statusKey),
        ];
    }

    /**
     * @param array{at: ?string, status: ?string} $cli
     * @param array{at: ?string, status: ?string} $http
     *
     * @return array{at: ?string, status: ?string, via: ?string}
     */
    private function latestHostingRun(array $cli, array $http): array
    {
        $cliAt = $this->parseTime($cli['at'] ?? null);
        $httpAt = $this->parseTime($http['at'] ?? null);

        if ($cliAt === null && $httpAt === null) {
            return ['at' => null, 'status' => null, 'via' => null];
        }

        if ($httpAt !== null && ($cliAt === null || $httpAt >= $cliAt)) {
            return ['at' => $http['at'], 'status' => $http['status'], 'via' => self::SOURCE_HTTP];
        }

        return ['at' => $cli['at'], 'status' => $cli['status'], 'via' => self::SOURCE_CLI];
    }

    private function isHostingStale(?string $at): bool
    {
        $time = $this->parseTime($at);

        if ($time === null) {
            return true;
        }

        return (time() - $time) > self::HOSTING_STALE_SECONDS;
    }

    private function nextScheduledAt(): string
    {
        $now = time();
        $todayRun = mktime(self::SCHEDULE_HOUR, 0, 0, (int) date('n', $now), (int) date('j', $now), (int) date('Y', $now));
        $next = $now < $todayRun ? $todayRun : strtotime('+1 day', $todayRun);

        return date('c', $next);
    }

    private function parseTime(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $time = strtotime($value);

        return $time === false ? null : $time;
    }

    private function wasRunRecently(): bool
    {
        $last = $this->settings->get(self::KEY_LAST_AT);
        $time = $this->parseTime($last);

        return $time !== null && (time() - $time) < CronInterval::DAILY_SECONDS;
    }

    private function record(string $source, string $status, string $message, string $output = ''): void
    {
        $at = date('c');
        $statusLine = $status . ': ' . $message;

        $payload = [
            self::KEY_LAST_AT => $at,
            self::KEY_LAST_STATUS => $statusLine,
            self::KEY_LAST_OUTPUT => mb_substr($output, 0, 12000),
        ];

        if (isset(self::SOURCE_KEYS[$source])) {
            [$atKey, $statusKey] = self::SOURCE_KEYS[$source];
            $payload[$atKey] = $at;
            $payload[$statusKey] = $statusLine;
        }

        $this->settings->setMany($payload);
    }
}

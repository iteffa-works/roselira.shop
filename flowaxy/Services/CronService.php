<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Repositories\Contracts\SettingsRepositoryInterface;
use Flowaxy\Support\Logger;

final class CronService
{
    private const KEY_LAST_AT = 'cron_last_run_at';
    private const KEY_LAST_STATUS = 'cron_last_run_status';
    private const KEY_LAST_OUTPUT = 'cron_last_run_output';

    public function __construct(
        private readonly GitUpdateService $gitUpdate,
        private readonly SystemCheckService $systemCheck,
        private readonly SeoFilesService $seoFiles,
        private readonly SettingsRepositoryInterface $settings,
    ) {
    }

    /** @return array{success: bool, message: string, output: string, skipped?: bool, checks?: array<string, mixed>} */
    public function runDaily(bool $forceDaily = true): array
    {
        $lines = [];
        $hasError = false;

        if ($forceDaily && !$this->gitUpdate->shouldRunDaily() && $this->wasRunRecently()) {
            $this->record('skipped', 'Cron вже виконувався сьогодні.');

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

        $output = implode("\n", $lines);
        $message = $hasError ? 'Cron завершено з помилками.' : 'Cron виконано успішно.';
        $this->record($hasError ? 'error' : 'ok', $message, $output);

        if ($hasError) {
            Logger::error('Cron completed with errors', ['output' => $output]);
        } else {
            Logger::info('Cron completed successfully');
        }

        return [
            'success' => !$hasError,
            'message' => $message,
            'output' => $output,
            'checks' => $checks,
        ];
    }

    /** @return array{last_run_at: ?string, last_status: ?string, last_output: ?string} */
    public function getCronStatus(): array
    {
        return [
            'last_run_at' => $this->settings->get(self::KEY_LAST_AT),
            'last_status' => $this->settings->get(self::KEY_LAST_STATUS),
            'last_output' => $this->settings->get(self::KEY_LAST_OUTPUT),
        ];
    }

    private function wasRunRecently(): bool
    {
        $last = $this->settings->get(self::KEY_LAST_AT);
        if ($last === null || $last === '') {
            return false;
        }

        $time = strtotime($last);

        return $time !== false && (time() - $time) < 82800;
    }

    private function record(string $status, string $message, string $output = ''): void
    {
        $this->settings->setMany([
            self::KEY_LAST_AT => date('c'),
            self::KEY_LAST_STATUS => $status . ': ' . $message,
            self::KEY_LAST_OUTPUT => mb_substr($output, 0, 12000),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Repositories\Contracts\SettingsRepositoryInterface;
use Flowaxy\Support\Logger;

final class GitUpdateService
{
    private const KEY_LAST_AT = 'git_last_update_at';
    private const KEY_LAST_COMMIT = 'git_last_update_commit';
    private const KEY_LAST_STATUS = 'git_last_update_status';
    private const KEY_LAST_OUTPUT = 'git_last_update_output';

    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
        private readonly string $projectRoot,
        private readonly string $repoUrl,
        private readonly string $branch,
    ) {
    }

    /** @return array<string, mixed> */
    public function getStatus(): array
    {
        $gitDir = $this->projectRoot . '/.git';

        if (!is_dir($gitDir)) {
            return [
                'available' => false,
                'git_installed' => $this->gitInstalled(),
                'is_repo' => false,
                'message' => 'Проєкт не є git-репозиторієм. На хостингу: git clone ' . $this->repoUrl . ' .',
            ];
        }

        if (!$this->gitInstalled()) {
            return [
                'available' => false,
                'git_installed' => false,
                'is_repo' => true,
                'message' => 'Git не знайдено на сервері.',
            ];
        }

        $this->run(['fetch', 'origin', $this->branch]);

        $local = $this->run(['rev-parse', 'HEAD']);
        $remote = $this->run(['rev-parse', 'origin/' . $this->branch]);
        $localHash = $this->firstLine($local['output']);
        $remoteHash = $this->firstLine($remote['output']);
        $behind = 0;

        if ($local['ok'] && $remote['ok'] && $localHash !== '' && $remoteHash !== '') {
            $count = $this->run(['rev-list', '--count', 'HEAD..' . 'origin/' . $this->branch]);
            $behind = (int) ($this->firstLine($count['output']) ?: 0);
        }

        $subject = $this->run(['log', '-1', '--pretty=format:%s']);
        $date = $this->run(['log', '-1', '--pretty=format:%ci']);

        return [
            'available' => true,
            'git_installed' => true,
            'is_repo' => true,
            'repo_url' => $this->detectRemoteUrl() ?: $this->repoUrl,
            'branch' => $this->currentBranch() ?: $this->branch,
            'local_commit' => $localHash,
            'local_subject' => $this->firstLine($subject['output']),
            'local_date' => $this->firstLine($date['output']),
            'remote_commit' => $remoteHash,
            'behind' => $behind,
            'updates_available' => $behind > 0,
            'last_update_at' => $this->settings->get(self::KEY_LAST_AT),
            'last_update_commit' => $this->settings->get(self::KEY_LAST_COMMIT),
            'last_update_status' => $this->settings->get(self::KEY_LAST_STATUS),
            'last_update_output' => $this->settings->get(self::KEY_LAST_OUTPUT),
        ];
    }

    /** @return array{success: bool, message: string, output: string, skipped?: bool} */
    public function pull(bool $forceDaily = false): array
    {
        $status = $this->getStatus();

        if (!($status['available'] ?? false)) {
            return [
                'success' => false,
                'message' => (string) ($status['message'] ?? 'Git недоступний'),
                'output' => '',
            ];
        }

        if ($forceDaily && !$this->shouldRunDaily()) {
            return [
                'success' => true,
                'message' => 'Оновлення вже виконувалось сьогодні.',
                'output' => '',
                'skipped' => true,
            ];
        }

        if (!($status['updates_available'] ?? false)) {
            $this->recordResult(true, 'Вже актуальна версія.', '', (string) ($status['local_commit'] ?? ''));

            return [
                'success' => true,
                'message' => 'Вже актуальна версія.',
                'output' => '',
            ];
        }

        $outputLines = [];
        $fetch = $this->run(['fetch', 'origin', $this->branch]);
        $outputLines = array_merge($outputLines, $fetch['output']);

        if (!$fetch['ok']) {
            $output = implode("\n", $outputLines);
            $this->recordResult(false, 'git fetch не вдався.', $output, '');

            return [
                'success' => false,
                'message' => 'git fetch не вдався.',
                'output' => $output,
            ];
        }

        $pull = $this->run(['merge', '--ff-only', 'origin/' . $this->branch]);
        $outputLines = array_merge($outputLines, $pull['output']);

        if (!$pull['ok']) {
            $output = implode("\n", $outputLines);
            $this->recordResult(false, 'git merge не вдався.', $output, '');
            Logger::error('Git update failed', ['output' => $output]);

            return [
                'success' => false,
                'message' => 'git merge не вдався. Перевірте права та стан репозиторію.',
                'output' => $output,
            ];
        }

        $newStatus = $this->getStatus();
        $commit = (string) ($newStatus['local_commit'] ?? '');
        $output = implode("\n", $outputLines);
        $this->recordResult(true, 'Оновлено успішно.', $output, $commit);
        Logger::info('Git update success', ['commit' => $commit]);

        return [
            'success' => true,
            'message' => 'Оновлено до ' . substr($commit, 0, 7) . '.',
            'output' => $output,
        ];
    }

    public function shouldRunDaily(): bool
    {
        $last = $this->settings->get(self::KEY_LAST_AT);
        if ($last === null || $last === '') {
            return true;
        }

        $lastTime = strtotime($last);
        if ($lastTime === false) {
            return true;
        }

        return (time() - $lastTime) >= 82800;
    }

    private function recordResult(bool $success, string $message, string $output, string $commit): void
    {
        $this->settings->setMany([
            self::KEY_LAST_AT => date('c'),
            self::KEY_LAST_COMMIT => $commit,
            self::KEY_LAST_STATUS => ($success ? 'ok' : 'error') . ': ' . $message,
            self::KEY_LAST_OUTPUT => mb_substr($output, 0, 8000),
        ]);
    }

    private function gitInstalled(): bool
    {
        $result = $this->run(['--version']);

        return $result['ok'];
    }

    private function currentBranch(): string
    {
        $result = $this->run(['rev-parse', '--abbrev-ref', 'HEAD']);

        return $this->firstLine($result['output']);
    }

    private function detectRemoteUrl(): string
    {
        $result = $this->run(['remote', 'get-url', 'origin']);

        return $this->firstLine($result['output']);
    }

    /** @return array{ok: bool, output: list<string>} */
    private function run(array $args): array
    {
        $command = 'git -c ' . escapeshellarg('safe.directory=' . $this->projectRoot);
        foreach ($args as $arg) {
            $command .= ' ' . escapeshellarg((string) $arg);
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $this->projectRoot);
        if (!is_resource($process)) {
            return ['ok' => false, 'output' => ['Не вдалося запустити git.']];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $lines = array_values(array_filter(array_map('trim', explode("\n", trim($stdout . "\n" . $stderr)))));

        return [
            'ok' => $exitCode === 0,
            'output' => $lines,
        ];
    }

    private function firstLine(array $lines): string
    {
        return $lines[0] ?? '';
    }
}

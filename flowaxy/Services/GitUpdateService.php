<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Repositories\Contracts\SettingsRepositoryInterface;
use Flowaxy\Support\CronInterval;
use Flowaxy\Support\Logger;

final class GitUpdateService
{
    private const KEY_LAST_AT = 'git_last_update_at';
    private const KEY_LAST_COMMIT = 'git_last_update_commit';
    private const KEY_LAST_STATUS = 'git_last_update_status';
    private const KEY_LAST_OUTPUT = 'git_last_update_output';

    private ?string $resolvedBinary = null;
    private bool $binaryResolved = false;

    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
        private readonly string $projectRoot,
        private readonly string $repoUrl,
        private readonly string $branch,
        private readonly string $configuredBinary = '',
    ) {
    }

    /** @return array<string, mixed> */
    public function getStatus(): array
    {
        $gitDir = $this->projectRoot . '/.git';
        $binary = $this->resolveBinary();

        if (!is_dir($gitDir)) {
            return [
                'available' => false,
                'git_installed' => $binary !== null,
                'git_binary' => $binary,
                'is_repo' => false,
                'can_update' => false,
                'message' => 'Проєкт не є git-репозиторієм. cd у корінь сайту → git clone ' . $this->repoUrl . ' . (крапка в кінці!)',
            ];
        }

        if ($binary === null) {
            return [
                'available' => false,
                'git_installed' => false,
                'git_binary' => null,
                'is_repo' => true,
                'can_update' => false,
                'message' => 'Git не знайдено. Вкажіть GIT_BINARY=/usr/bin/git у .env або попросіть хостинг увімкнути git.',
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
        $remoteSubject = $this->run(['log', '-1', '--pretty=format:%s', 'origin/' . $this->branch]);
        $remoteDate = $this->run(['log', '-1', '--pretty=format:%ci', 'origin/' . $this->branch]);

        return [
            'available' => true,
            'git_installed' => true,
            'git_binary' => $binary,
            'is_repo' => true,
            'can_update' => true,
            'repo_url' => $this->detectRemoteUrl() ?: $this->repoUrl,
            'branch' => $this->currentBranch() ?: $this->branch,
            'local_commit' => $localHash,
            'local_subject' => $this->firstLine($subject['output']),
            'local_date' => $this->firstLine($date['output']),
            'remote_commit' => $remoteHash,
            'remote_subject' => $this->firstLine($remoteSubject['output']),
            'remote_date' => $this->firstLine($remoteDate['output']),
            'behind' => $behind,
            'updates_available' => $behind > 0,
            'last_update_at' => $this->settings->get(self::KEY_LAST_AT),
            'last_update_commit' => $this->settings->get(self::KEY_LAST_COMMIT),
            'last_update_status' => $this->settings->get(self::KEY_LAST_STATUS),
            'last_update_output' => $this->settings->get(self::KEY_LAST_OUTPUT),
        ];
    }

    /** @return array{success: bool, message: string, output: string, skipped?: bool} */
    public function pull(bool $forceDaily = false, bool $forcePull = false): array
    {
        $status = $this->getStatus();

        if (!($status['can_update'] ?? false)) {
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

        if (!$forcePull && !($status['updates_available'] ?? false)) {
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

        if (!$forcePull && !($this->getStatus()['updates_available'] ?? false)) {
            $commit = (string) ($status['local_commit'] ?? '');
            $this->recordResult(true, 'Вже актуальна версія.', implode("\n", $outputLines), $commit);

            return [
                'success' => true,
                'message' => 'Вже актуальна версія.',
                'output' => implode("\n", $outputLines),
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
        $hadUpdates = ($status['local_commit'] ?? '') !== $commit;
        $message = $hadUpdates
            ? 'Оновлено до ' . substr($commit, 0, 7) . '.'
            : 'Перевірено — вже актуальна версія.';
        $this->recordResult(true, $message, $output, $commit);
        Logger::info('Git update success', ['commit' => $commit]);

        return [
            'success' => true,
            'message' => $message,
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

        return (time() - $lastTime) >= CronInterval::DAILY_SECONDS;
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

    private function resolveBinary(): ?string
    {
        if ($this->binaryResolved) {
            return $this->resolvedBinary;
        }

        $this->binaryResolved = true;
        $candidates = [];

        if ($this->configuredBinary !== '') {
            $candidates[] = $this->configuredBinary;
        }

        $candidates = array_merge($candidates, [
            'git',
            '/usr/bin/git',
            '/usr/local/bin/git',
            '/bin/git',
            'C:\\Program Files\\Git\\cmd\\git.exe',
            'C:\\Program Files\\Git\\bin\\git.exe',
            'C:\\Program Files (x86)\\Git\\cmd\\git.exe',
            'C:\\Program Files (x86)\\Git\\bin\\git.exe',
            'C:\\OSPanel\\modules\\Git\\bin\\git.exe',
        ]);

        if (DIRECTORY_SEPARATOR === '\\') {
            $fromPath = $this->resolveGitFromWindowsPath();
            if ($fromPath !== null) {
                array_unshift($candidates, $fromPath);
            }
        }

        foreach ($candidates as $candidate) {
            if ($this->binaryWorks($candidate)) {
                $this->resolvedBinary = $candidate;

                return $this->resolvedBinary;
            }
        }

        $this->resolvedBinary = null;

        return null;
    }

    private function binaryWorks(string $binary): bool
    {
        if ($binary !== 'git') {
            if (!file_exists($binary)) {
                return false;
            }

            if (DIRECTORY_SEPARATOR !== '\\' && !is_executable($binary)) {
                return false;
            }
        }

        $result = $this->runWithBinary($binary, ['--version']);

        return $result['ok'];
    }

    /** @return array{ok: bool, output: list<string>} */
    private function run(array $args): array
    {
        $binary = $this->resolveBinary();
        if ($binary === null) {
            return ['ok' => false, 'output' => ['Git binary not found.']];
        }

        return $this->runWithBinary($binary, $args);
    }

    /** @return array{ok: bool, output: list<string>} */
    private function runWithBinary(string $binary, array $args): array
    {
        $command = escapeshellarg($binary) . ' -c ' . escapeshellarg('safe.directory=' . $this->projectRoot);
        foreach ($args as $arg) {
            $command .= ' ' . escapeshellarg((string) $arg);
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open(
            $command,
            $descriptors,
            $pipes,
            $this->projectRoot,
            null,
            ['bypass_shell' => $this->shouldBypassShell($binary)],
        );
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

    private function firstLine(array $lines): string
    {
        return $lines[0] ?? '';
    }

    private function shouldBypassShell(string $binary): bool
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            return true;
        }

        return preg_match('/^[A-Za-z]:[\\\\\\/].*\.exe$/i', $binary) === 1
            || str_contains($binary, '\\')
            || str_contains($binary, '/');
    }

    private function resolveGitFromWindowsPath(): ?string
    {
        $output = [];
        $code = 1;
        @exec('where git 2>nul', $output, $code);
        if ($code !== 0 || $output === []) {
            return null;
        }

        foreach ($output as $line) {
            $line = trim($line);
            if ($line !== '' && is_file($line)) {
                return $line;
            }
        }

        return null;
    }
}

<?php

declare(strict_types=1);

/**
 * Щоденний cron: git pull + system checks.
 *
 * CLI:  php cron.php
 * HTTP: /cron.php?token=CRON_SECRET
 *
 * Crontab: 0 4 * * * /usr/bin/php /path/to/roselira.shop/cron.php
 */

$projectRoot = __DIR__;
$isCli = PHP_SAPI === 'cli';

require_once $projectRoot . '/flowaxy/Support/env.php';
flowaxy_load_env($projectRoot . '/.env');

$secret = trim((string) (flowaxy_env('CRON_SECRET', '') ?? ''));

if (!$isCli) {
    if ($secret === '') {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Forbidden\n";
        exit;
    }

    $token = (string) ($_GET['token'] ?? '');
    if (!hash_equals($secret, $token)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Forbidden\n";
        exit;
    }
}

require_once $projectRoot . '/flowaxy/cli-bootstrap.php';

use Flowaxy\Services\CronService;

$ctx = flowaxy_cli_bootstrap($projectRoot);
$result = $ctx['cron']->runDaily(forceDaily: true, source: $isCli ? CronService::SOURCE_CLI : CronService::SOURCE_HTTP);

header('Content-Type: text/plain; charset=utf-8');

if (!empty($result['skipped'])) {
    echo 'SKIP: ' . $result['message'] . "\n";
    exit(0);
}

echo ($result['success'] ? 'OK' : 'ERROR') . ': ' . $result['message'] . "\n";

if (!empty($result['output'])) {
    echo $result['output'] . "\n";
}

exit($result['success'] ? 0 : 1);

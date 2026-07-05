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

$ctx = flowaxy_cli_bootstrap($projectRoot);

use Flowaxy\Repositories\Sqlite\SqliteVisitorRepository;
use Flowaxy\Services\CronService;
use Flowaxy\Services\GitUpdateService;
use Flowaxy\Services\ProductFeedService;
use Flowaxy\Services\SeoFilesService;
use Flowaxy\Services\SitemapService;
use Flowaxy\Services\SystemCheckService;
use Flowaxy\Services\TelegramNotificationService;
use Flowaxy\Services\VisitorAnalyticsService;

$config = $ctx['config'];
$feeds = new ProductFeedService($ctx['catalog']);
$telegram = new TelegramNotificationService($ctx['settings']);
$gitUpdate = new GitUpdateService(
    $ctx['settings'],
    $config['project_root'],
    $config['git_repo_url'],
    $config['git_branch'],
    (string) ($config['git_binary'] ?? ''),
);
$systemCheck = new SystemCheckService(
    $ctx['catalog'],
    $feeds,
    $telegram,
    $ctx['settings'],
    $config['project_root'],
);
$sitemap = new SitemapService($ctx['catalog']);
$seoFiles = new SeoFilesService($sitemap, $config['project_root']);
$visitorAnalytics = new VisitorAnalyticsService(new SqliteVisitorRepository($ctx['connection']));
$cron = new CronService($gitUpdate, $systemCheck, $seoFiles, $ctx['settings'], $visitorAnalytics);

$result = $cron->runDaily(forceDaily: true, source: $isCli ? CronService::SOURCE_CLI : CronService::SOURCE_HTTP);

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

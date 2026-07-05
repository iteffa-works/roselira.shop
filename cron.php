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

require_once __DIR__ . '/flowaxy/Support/env.php';

flowaxy_load_env(__DIR__ . '/.env');

$isCli = PHP_SAPI === 'cli';
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

require_once __DIR__ . '/flowaxy/Support/helpers.php';
require_once __DIR__ . '/flowaxy/Support/AppState.php';
require_once __DIR__ . '/flowaxy/Core/Autoloader.php';

use Flowaxy\Core\Autoloader;
use Flowaxy\Repositories\Sqlite\Connection;
use Flowaxy\Repositories\Sqlite\SqliteSettingsRepository;
use Flowaxy\Services\CatalogService;
use Flowaxy\Services\CronService;
use Flowaxy\Services\GitUpdateService;
use Flowaxy\Services\LocaleService;
use Flowaxy\Services\ProductFeedService;
use Flowaxy\Services\SystemCheckService;
use Flowaxy\Services\TelegramNotificationService;
use Flowaxy\Repositories\Sqlite\SqliteCatalogRepository;
use Flowaxy\Repositories\Sqlite\SqliteLocaleRepository;
use Flowaxy\Support\AppState;

Autoloader::boot(__DIR__);

$config = require __DIR__ . '/flowaxy/config.php';
AppState::$config = $config;

$connection = new Connection(
    $config['storage_path'] . '/roselira.db',
    $config['storage_path'] . '/roselira.sql',
);
$connection->restoreFromDumpIfEmpty();

$settings = new SqliteSettingsRepository($connection);
$catalogRepo = new SqliteCatalogRepository($connection);
$localeRepo = new SqliteLocaleRepository($connection);
$locale = new LocaleService(
    $localeRepo,
    $config['locales_public'],
    $config['locale_fallback'],
    $config['locale_default'],
    $config['locale_cookie'],
    $config['locale_editable'],
);
$catalog = new CatalogService($catalogRepo, $locale);
$feeds = new ProductFeedService($catalog);
$telegram = new TelegramNotificationService($settings);
$gitUpdate = new GitUpdateService(
    $settings,
    $config['project_root'],
    $config['git_repo_url'],
    $config['git_branch'],
);
$systemCheck = new SystemCheckService(
    $catalog,
    $feeds,
    $telegram,
    $settings,
    $config['project_root'],
);
$cron = new CronService($gitUpdate, $systemCheck, $settings);

$result = $cron->runDaily(forceDaily: true);

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

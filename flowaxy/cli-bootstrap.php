<?php

declare(strict_types=1);

/**
 * Shared bootstrap for CLI entrypoints (cron.php, generate-seo.php).
 *
 * @return array{
 *     config: array<string, mixed>,
 *     connection: Flowaxy\Repositories\Sqlite\Connection,
 *     catalog: Flowaxy\Services\CatalogService,
 *     locale: Flowaxy\Services\LocaleService,
 *     settings: Flowaxy\Repositories\Sqlite\SqliteSettingsRepository,
 *     cron: Flowaxy\Services\CronService,
 *     seoFiles: Flowaxy\Services\SeoFilesService,
 *     exchange: Flowaxy\Services\ExchangeService,
 *     analytics: Flowaxy\Services\VisitorAnalyticsService
 * }
 */
function flowaxy_cli_bootstrap(string $projectRoot): array
{
    require_once $projectRoot . '/flowaxy/Support/env.php';

    flowaxy_load_env($projectRoot . '/.env');
    require_once $projectRoot . '/flowaxy/Support/helpers.php';
    require_once $projectRoot . '/flowaxy/Support/AppState.php';
    require_once $projectRoot . '/flowaxy/Core/Autoloader.php';

    Flowaxy\Core\Autoloader::boot($projectRoot);

    $config = require $projectRoot . '/flowaxy/config.php';
    Flowaxy\Support\AppState::$config = $config;

    $connection = new Flowaxy\Repositories\Sqlite\Connection(
        $config['storage_path'] . '/roselira.db',
        $config['storage_path'] . '/roselira.sql',
    );
    $connection->restoreFromDumpIfEmpty();

    $settings = new Flowaxy\Repositories\Sqlite\SqliteSettingsRepository($connection);
    $localeRepo = new Flowaxy\Repositories\Sqlite\SqliteLocaleRepository($connection);
    $locale = new Flowaxy\Services\LocaleService(
        $localeRepo,
        $config['locales_public'],
        $config['locale_fallback'],
        $config['locale_default'],
        $config['locale_cookie'],
        $config['locale_editable'],
    );
    $catalog = new Flowaxy\Services\CatalogService(
        new Flowaxy\Repositories\Sqlite\SqliteCatalogRepository($connection),
        $locale,
        $settings,
    );

    $analytics = new Flowaxy\Services\VisitorAnalyticsService(
        new Flowaxy\Repositories\Sqlite\SqliteVisitorRepository($connection),
    );
    $telegram = new Flowaxy\Services\TelegramNotificationService($settings);
    $feeds = new Flowaxy\Services\ProductFeedService($catalog);
    $gitUpdate = new Flowaxy\Services\GitUpdateService(
        $settings,
        (string) $config['project_root'],
        (string) $config['git_repo_url'],
        (string) $config['git_branch'],
        (string) ($config['git_binary'] ?? ''),
    );
    $systemCheck = new Flowaxy\Services\SystemCheckService(
        $catalog,
        $feeds,
        $telegram,
        $settings,
        (string) $config['project_root'],
    );
    $sitemap = new Flowaxy\Services\SitemapService($catalog);
    $seoFiles = new Flowaxy\Services\SeoFilesService(
        $sitemap,
        (string) $config['project_root'],
    );
    $cron = new Flowaxy\Services\CronService(
        $gitUpdate,
        $systemCheck,
        $seoFiles,
        $settings,
        $analytics,
    );
    $exchange = new Flowaxy\Services\ExchangeService($catalog);

    return [
        'config' => $config,
        'connection' => $connection,
        'catalog' => $catalog,
        'locale' => $locale,
        'settings' => $settings,
        'cron' => $cron,
        'seoFiles' => $seoFiles,
        'exchange' => $exchange,
        'analytics' => $analytics,
    ];
}

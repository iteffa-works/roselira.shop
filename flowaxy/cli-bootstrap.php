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
 *     settings: Flowaxy\Repositories\Sqlite\SqliteSettingsRepository
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
    );

    return [
        'config' => $config,
        'connection' => $connection,
        'catalog' => $catalog,
        'locale' => $locale,
        'settings' => $settings,
    ];
}

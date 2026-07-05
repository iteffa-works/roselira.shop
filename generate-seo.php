<?php

declare(strict_types=1);

/**
 * Regenerate robots.txt and sitemap.xml in project root and public/.
 *
 * CLI: php generate-seo.php
 */

require_once __DIR__ . '/flowaxy/Support/env.php';

flowaxy_load_env(__DIR__ . '/.env');
require_once __DIR__ . '/flowaxy/Support/helpers.php';
require_once __DIR__ . '/flowaxy/Support/AppState.php';
require_once __DIR__ . '/flowaxy/Core/Autoloader.php';

use Flowaxy\Core\Autoloader;
use Flowaxy\Repositories\Sqlite\Connection;
use Flowaxy\Repositories\Sqlite\SqliteCatalogRepository;
use Flowaxy\Repositories\Sqlite\SqliteLocaleRepository;
use Flowaxy\Services\CatalogService;
use Flowaxy\Services\LocaleService;
use Flowaxy\Services\SeoFilesService;
use Flowaxy\Services\SitemapService;
use Flowaxy\Support\AppState;

Autoloader::boot(__DIR__);

$config = require __DIR__ . '/flowaxy/config.php';
AppState::$config = $config;

$connection = new Connection(
    $config['storage_path'] . '/roselira.db',
    $config['storage_path'] . '/roselira.sql',
);
$connection->restoreFromDumpIfEmpty();

$localeRepo = new SqliteLocaleRepository($connection);
$locale = new LocaleService(
    $localeRepo,
    $config['locales_public'],
    $config['locale_fallback'],
    $config['locale_default'],
    $config['locale_cookie'],
    $config['locale_editable'],
);
$catalog = new CatalogService(new SqliteCatalogRepository($connection), $locale);
$sitemap = new SitemapService($catalog);
$seoFiles = new SeoFilesService($sitemap, $config['project_root']);

$result = $seoFiles->sync();

fwrite(STDOUT, ($result['success'] ? 'OK' : 'ERROR') . ': ' . $result['message'] . PHP_EOL);

if (!$result['success']) {
    exit(1);
}

foreach ($result['files'] as $file) {
    fwrite(STDOUT, '  ' . $file . PHP_EOL);
}

exit(0);

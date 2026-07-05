<?php

declare(strict_types=1);

/**
 * Regenerate robots.txt and sitemap.xml in project root and public/.
 *
 * CLI: php generate-seo.php
 */

$projectRoot = __DIR__;
require_once $projectRoot . '/flowaxy/cli-bootstrap.php';

$ctx = flowaxy_cli_bootstrap($projectRoot);

use Flowaxy\Services\SeoFilesService;
use Flowaxy\Services\SitemapService;

$sitemap = new SitemapService($ctx['catalog']);
$seoFiles = new SeoFilesService($sitemap, $ctx['config']['project_root']);

$result = $seoFiles->sync();

fwrite(STDOUT, ($result['success'] ? 'OK' : 'ERROR') . ': ' . $result['message'] . PHP_EOL);

if (!$result['success']) {
    exit(1);
}

foreach ($result['files'] as $file) {
    fwrite(STDOUT, '  ' . $file . PHP_EOL);
}

exit(0);

<?php

declare(strict_types=1);

namespace Flowaxy\Controllers;

use Flowaxy\Core\Response;
use Flowaxy\Services\CatalogService;
use Flowaxy\Services\LocaleService;

final class SeoController
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly LocaleService $locale,
    ) {
    }

    public function sitemap(): Response
    {
        $base = rtrim(app_url(), '/');
        $urls = [
            ['loc' => $base . '/', 'priority' => '1.0'],
            ['loc' => $base . '/privacy', 'priority' => '0.3'],
            ['loc' => $base . '/terms', 'priority' => '0.3'],
            ['loc' => $base . '/delivery', 'priority' => '0.4'],
        ];

        foreach ($this->catalog->getActiveProducts('uk') as $product) {
            $slug = (string) ($product['slug'] ?? '');
            if ($slug === '') {
                continue;
            }

            $urls[] = [
                'loc' => $base . '/' . rawurlencode($slug),
                'priority' => '0.8',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>' . htmlspecialchars($url['loc'], ENT_XML1) . '</loc>';
            $xml .= '<priority>' . $url['priority'] . '</priority></url>' . "\n";
        }

        $xml .= '</urlset>';

        return Response::xml($xml);
    }
}

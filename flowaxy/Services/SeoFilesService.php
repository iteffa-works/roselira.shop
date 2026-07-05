<?php

declare(strict_types=1);

namespace Flowaxy\Services;

final class SeoFilesService
{
    public function __construct(
        private readonly SitemapService $sitemap,
        private readonly string $projectRoot,
    ) {
    }

    /** @return array{success: bool, message: string, files: list<string>} */
    public function sync(): array
    {
        $robots = $this->buildRobotsTxt();
        $sitemap = $this->sitemap->buildXml();
        $targets = [
            $this->projectRoot . '/robots.txt',
            $this->projectRoot . '/public/robots.txt',
            $this->projectRoot . '/sitemap.xml',
            $this->projectRoot . '/public/sitemap.xml',
        ];

        $written = [];
        foreach ($targets as $path) {
            if (!$this->writeFile($path, str_ends_with($path, 'robots.txt') ? $robots : $sitemap)) {
                return [
                    'success' => false,
                    'message' => 'Не вдалося записати ' . $path,
                    'files' => $written,
                ];
            }

            $written[] = $path;
        }

        return [
            'success' => true,
            'message' => 'Оновлено robots.txt і sitemap.xml (' . count($this->sitemap->urls()) . ' URL)',
            'files' => $written,
        ];
    }

    private function buildRobotsTxt(): string
    {
        $base = rtrim(app_url(), '/');

        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            '',
            'Sitemap: ' . $base . '/sitemap.xml',
            '',
        ]);
    }

    private function writeFile(string $path, string $content): bool
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        return file_put_contents($path, $content) !== false;
    }
}

<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Repositories\Contracts\SettingsRepositoryInterface;

final class SystemCheckService
{
    private const KEY_CHECKS = 'system_checks_json';

    public function __construct(
        private readonly CatalogService $catalog,
        private readonly ProductFeedService $feeds,
        private readonly TelegramNotificationService $telegram,
        private readonly SettingsRepositoryInterface $settings,
        private readonly string $projectRoot,
    ) {
    }

    /** @return array{checked_at: string, summary: array{ok: int, warn: int, error: int}, items: list<array<string, mixed>>} */
    public function runAll(): array
    {
        $items = [
            $this->checkAppUrl(),
            $this->checkHttps(),
            $this->checkStorage(),
            $this->checkGoogleFeed(),
            $this->checkMetaFeed(),
            $this->checkFeedSecret(),
            $this->checkSitemap(),
            $this->checkRobots(),
            $this->checkAnalytics(),
            $this->checkTelegram(),
            $this->checkCatalog(),
            $this->checkPublicFeedUrl('google_feed_url', '/feeds/google.xml', 'Google feed (URL)'),
            $this->checkPublicFeedUrl('meta_feed_url', '/feeds/meta.xml', 'Meta feed (URL)'),
            $this->checkPublicUrl('sitemap_url', '/sitemap.xml', 'Sitemap (URL)'),
        ];

        $summary = ['ok' => 0, 'warn' => 0, 'error' => 0];
        foreach ($items as $item) {
            $summary[$item['status']]++;
        }

        $result = [
            'checked_at' => date('c'),
            'summary' => $summary,
            'items' => $items,
        ];

        $this->settings->setMany([
            self::KEY_CHECKS => json_encode($result, JSON_UNESCAPED_UNICODE),
        ]);

        return $result;
    }

    /** @return array<string, mixed>|null */
    public function lastResults(): ?array
    {
        $raw = $this->settings->get(self::KEY_CHECKS);
        if ($raw === null || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @return array<string, mixed> */
    private function checkAppUrl(): array
    {
        $url = (string) (app_config()['app_url'] ?? '');
        $isProd = (app_config()['app_env'] ?? '') === 'production';

        if ($url === '') {
            return $this->item('app_url', 'APP_URL', $isProd ? 'error' : 'warn', 'Не задано в .env', '');
        }

        return $this->item('app_url', 'APP_URL', 'ok', $url, $url);
    }

    /** @return array<string, mixed> */
    private function checkHttps(): array
    {
        $secure = (bool) (app_config()['session_secure'] ?? false);
        $isHttps = request_is_https();

        if ($secure && !$isHttps) {
            return $this->item('https', 'HTTPS', 'warn', 'SESSION_SECURE=true, але запит не через HTTPS', '');
        }

        return $this->item('https', 'HTTPS', $isHttps || !$secure ? 'ok' : 'warn', $isHttps ? 'Активний' : 'Локально / без SSL', '');
    }

    /** @return array<string, mixed> */
    private function checkStorage(): array
    {
        $path = (string) (app_config()['storage_path'] ?? '');
        if ($path === '' || !is_dir($path)) {
            return $this->item('storage', 'Storage', 'error', 'Каталог storage недоступний', '');
        }

        if (!is_writable($path)) {
            return $this->item('storage', 'Storage', 'error', 'storage/ не writable', $path);
        }

        return $this->item('storage', 'Storage', 'ok', 'Writable', $path);
    }

    /** @return array<string, mixed> */
    private function checkGoogleFeed(): array
    {
        return $this->checkFeedInternal('google_feed', 'Google Merchant feed', fn(): string => $this->feeds->buildGoogleXml());
    }

    /** @return array<string, mixed> */
    private function checkMetaFeed(): array
    {
        return $this->checkFeedInternal('meta_feed', 'Meta Catalog feed', fn(): string => $this->feeds->buildMetaXml());
    }

    /** @return array<string, mixed> */
    private function checkFeedInternal(string $id, string $label, callable $builder): array
    {
        $items = $this->feeds->getItems();
        $url = $this->feedUrl($id === 'google_feed' ? '/feeds/google.xml' : '/feeds/meta.xml');

        if ($items === []) {
            return $this->item($id, $label, 'error', 'Немає товарів з ціною для feed', $url);
        }

        $xml = $builder();
        $doc = @simplexml_load_string($xml);
        if ($doc === false) {
            return $this->item($id, $label, 'error', 'Невалідний XML', $url);
        }

        $withImages = 0;
        foreach ($items as $item) {
            if (!str_contains($item['image'], 'placeholder.svg')) {
                $withImages++;
            }
        }

        $status = $withImages < count($items) ? 'warn' : 'ok';
        $message = count($items) . ' товар(ів), ' . $withImages . ' з фото';

        return $this->item($id, $label, $status, $message, $url);
    }

    /** @return array<string, mixed> */
    private function checkFeedSecret(): array
    {
        $secret = (string) (app_config()['feed_secret'] ?? '');
        $isProd = (app_config()['app_env'] ?? '') === 'production';

        if ($secret === '') {
            return $this->item('feed_secret', 'FEED_SECRET', $isProd ? 'warn' : 'ok', 'Не задано — feeds відкриті', '');
        }

        return $this->item('feed_secret', 'FEED_SECRET', 'ok', 'Захист увімкнено', '');
    }

    /** @return array<string, mixed> */
    private function checkSitemap(): array
    {
        $products = $this->catalog->getActiveProducts('uk');
        $count = count($products) + 4;
        $url = absolute_url('/sitemap.xml');

        if ($products === []) {
            return $this->item('sitemap', 'Sitemap.xml', 'warn', 'Немає активних товарів', $url);
        }

        return $this->item('sitemap', 'Sitemap.xml', 'ok', $count . ' URL', $url);
    }

    /** @return array<string, mixed> */
    private function checkRobots(): array
    {
        $path = $this->projectRoot . '/public/robots.txt';
        $url = absolute_url('/robots.txt');

        if (!is_file($path)) {
            return $this->item('robots', 'robots.txt', 'error', 'Файл відсутній', $url);
        }

        $content = (string) file_get_contents($path);

        return $this->item(
            'robots',
            'robots.txt',
            str_contains($content, 'Sitemap:') ? 'ok' : 'warn',
            str_contains($content, 'Disallow: /admin') ? 'OK + Sitemap' : 'Перевірте вміст',
            $url,
        );
    }

    /** @return array<string, mixed> */
    private function checkAnalytics(): array
    {
        $meta = (string) (app_config()['meta_pixel_id'] ?? '');
        $ga4 = (string) (app_config()['ga4_measurement_id'] ?? '');
        $gtm = (string) (app_config()['gtm_container_id'] ?? '');
        $isProd = (app_config()['app_env'] ?? '') === 'production';

        if ($meta === '' && $ga4 === '' && $gtm === '') {
            return $this->item('analytics', 'Analytics / Pixel', $isProd ? 'warn' : 'ok', 'Не налаштовано в .env', '');
        }

        $parts = array_filter([
            $meta !== '' ? 'Meta' : '',
            $ga4 !== '' ? 'GA4' : '',
            $gtm !== '' ? 'GTM' : '',
        ]);

        return $this->item('analytics', 'Analytics / Pixel', 'ok', implode(', ', $parts), '');
    }

    /** @return array<string, mixed> */
    private function checkTelegram(): array
    {
        $config = $this->telegram->getConfig();

        if (!$config['enabled']) {
            return $this->item('telegram', 'Telegram', 'warn', 'Вимкнено', admin_url('notifications'));
        }

        if ($config['bot_token'] === '' || $config['chat_id'] === '') {
            return $this->item('telegram', 'Telegram', 'error', 'Token або Chat ID порожні', admin_url('notifications'));
        }

        return $this->item('telegram', 'Telegram', 'ok', 'Увімкнено', admin_url('notifications'));
    }

    /** @return array<string, mixed> */
    private function checkCatalog(): array
    {
        $active = $this->catalog->getActiveProducts('uk');
        $withPrice = 0;

        foreach ($active as $product) {
            if (isset($product['price'])) {
                $withPrice++;
            }
        }

        if ($active === []) {
            return $this->item('catalog', 'Каталог', 'error', 'Немає активних товарів', admin_url('catalog'));
        }

        $status = $withPrice === count($active) ? 'ok' : 'warn';

        return $this->item(
            'catalog',
            'Каталог',
            $status,
            count($active) . ' активних, ' . $withPrice . ' з ціною',
            admin_url('catalog'),
        );
    }

    /** @return array<string, mixed> */
    private function checkPublicUrl(string $id, string $path, string $label): array
    {
        return $this->fetchUrlCheck($id, $label, $this->publicUrl($path));
    }

    /** @return array<string, mixed> */
    private function checkPublicFeedUrl(string $id, string $path, string $label): array
    {
        return $this->fetchUrlCheck($id, $label, $this->feedUrl($path));
    }

    /** @return array<string, mixed> */
    private function fetchUrlCheck(string $id, string $label, string $url): array
    {
        if ($url === '') {
            return $this->item($id, $label, 'warn', 'APP_URL не задано — перевірка URL пропущена', '');
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'ignore_errors' => true,
                'header' => "User-Agent: RoseliraAdminCheck/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        $statusCode = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $statusCode = (int) $m[1];
        }

        if ($body === false || $statusCode >= 400) {
            return $this->item($id, $label, 'error', 'HTTP ' . ($statusCode ?: '—'), $url);
        }

        return $this->item($id, $label, 'ok', 'HTTP ' . $statusCode, $url);
    }

    private function feedUrl(string $path): string
    {
        $url = $this->publicUrl($path);
        $secret = (string) (app_config()['feed_secret'] ?? '');
        if ($secret !== '' && $url !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'token=' . rawurlencode($secret);
        }

        return $url;
    }

    private function publicUrl(string $path): string
    {
        $base = (string) (app_config()['app_url'] ?? '');
        if ($base === '') {
            return '';
        }

        return rtrim($base, '/') . $path;
    }

    /** @return array<string, mixed> */
    private function item(string $id, string $label, string $status, string $message, string $url): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'status' => $status,
            'message' => $message,
            'url' => $url,
        ];
    }
}

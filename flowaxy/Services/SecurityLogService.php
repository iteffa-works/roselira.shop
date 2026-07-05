<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Repositories\Contracts\SecurityRepositoryInterface;
use Flowaxy\Support\RequestContext;

final class SecurityLogService
{
    private const ORDER_MAX = 5;
    private const LOGIN_MAX = 5;
    private const WINDOW_SECONDS = 900;

    public function __construct(private readonly SecurityRepositoryInterface $security)
    {
    }

    public function isOrderLimited(?string $ip = null): bool
    {
        return $this->isLimited('order', self::ORDER_MAX, $ip);
    }

    public function isLoginLimited(?string $ip = null): bool
    {
        return $this->isLimited('login', self::LOGIN_MAX, $ip);
    }

    public function hitOrder(?string $ip = null): void
    {
        $this->hit('order', $ip);
    }

    public function hitLogin(?string $ip = null): void
    {
        $this->hit('login', $ip);
    }

    public function clearOrderLimit(?string $ip = null): int
    {
        return $this->security->clearRateLimit('order', $ip);
    }

    public function clearLoginLimit(?string $ip = null): int
    {
        return $this->security->clearRateLimit('login', $ip);
    }

    public function clearAllRateLimits(): int
    {
        return $this->security->clearRateLimit('order')
            + $this->security->clearRateLimit('login');
    }

    public function log(string $eventType, string $verdict, array $meta = []): void
    {
        $this->security->logEvent(
            $eventType,
            $verdict,
            RequestContext::clientIp(),
            RequestContext::userAgent(),
            RequestContext::requestPath(),
            RequestContext::requestMethod(),
            $meta,
        );
    }

    /** @return list<array<string, mixed>> */
    public function listEvents(array $filters = [], int $limit = 100): array
    {
        return $this->security->listEvents($filters, $limit);
    }

    /** @return array{total: int, fraud: int, suspect: int, ok: int, rate_limited: int} */
    public function stats(): array
    {
        return $this->security->stats();
    }

    public function deleteEventsOlderThan(int $days): int
    {
        return $this->security->deleteEventsOlderThan($days);
    }

    public function deleteAllEvents(): int
    {
        return $this->security->deleteAllEvents();
    }

    /** @return array<string, string> */
    public static function eventLabels(): array
    {
        return [
            'order_success' => 'Замовлення OK',
            'order_rate_limited' => 'Ліміт замовлень',
            'order_honeypot' => 'Honeypot (бот)',
            'order_validation' => 'Помилка форми',
            'order_save_failed' => 'Помилка збереження',
            'login_success' => 'Вхід OK',
            'login_failed' => 'Невірний логін',
            'login_rate_limited' => 'Ліміт входу',
        ];
    }

    /** @return array<string, string> */
    public static function verdictLabels(): array
    {
        return [
            'ok' => 'Клієнт',
            'suspect' => 'Підозріло',
            'fraud' => 'Мошенник',
        ];
    }

    private function isLimited(string $scope, int $max, ?string $ip): bool
    {
        $ip = $ip ?? RequestContext::clientIp();
        $this->security->pruneRateHits(self::WINDOW_SECONDS);

        return $this->security->countRecentRateHits($scope, $ip, self::WINDOW_SECONDS) >= $max;
    }

    private function hit(string $scope, ?string $ip): void
    {
        $this->security->recordRateHit($scope, $ip ?? RequestContext::clientIp());
    }
}

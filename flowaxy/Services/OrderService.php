<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Core\Request;
use Flowaxy\Repositories\Contracts\OrderRepositoryInterface;
use Flowaxy\Support\Logger;
use Flowaxy\Services\SecurityLogService;

final class OrderService
{
    /** @param list<string> $statuses */
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CatalogService $catalog,
        private readonly LocaleService $locale,
        private readonly TelegramNotificationService $telegram,
        private readonly SecurityLogService $security,
        private readonly array $statuses,
    ) {
    }

    /** @return list<string> */
    public function statuses(): array
    {
        return $this->statuses;
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->orders->all();
    }

    public function findById(string $id): ?array
    {
        return $this->orders->findById($id);
    }

    public function save(array $order): bool
    {
        return $this->orders->save($order);
    }

    public function isValidStatus(string $status): bool
    {
        return in_array($status, $this->statuses, true);
    }

    public function deleteById(string $id): bool
    {
        return $this->orders->deleteById($id);
    }

    /** @param list<string> $statuses */
    public function deleteByStatuses(array $statuses): int
    {
        $valid = array_values(array_filter($statuses, fn(string $s): bool => $this->isValidStatus($s)));

        return $this->orders->deleteByStatuses($valid);
    }

    public function deleteAll(): int
    {
        return $this->orders->deleteAll();
    }

    /**
     * @param 'all'|'within_last'|'older_than' $scope
     * @param list<string>|null $statuses
     */
    public function deleteByPeriod(string $scope, int $periodDays = 0, ?array $statuses = null): int
    {
        if ($statuses !== null) {
            $statuses = array_values(array_filter(
                $statuses,
                fn(string $status): bool => $this->isValidStatus($status),
            ));
            if ($statuses === []) {
                $statuses = null;
            }
        }

        return $this->orders->deleteByPeriod($scope, $periodDays, $statuses);
    }

    /** @return array<string, int> */
    public function countByStatus(): array
    {
        return $this->orders->countByStatus();
    }

    /** @return array{success: bool, message: string, order_id?: string, status: int} */
    public function createFromRequest(Request $request): array
    {
        $slug = trim((string) $request->post('product_slug', ''));
        $variantId = trim((string) $request->post('variant_id', ''));
        $name = trim((string) $request->post('name', ''));
        $phone = trim((string) $request->post('phone', ''));
        $comment = trim((string) $request->post('comment', ''));
        $honeypot = trim((string) $request->post('website', ''));

        if ($honeypot !== '') {
            $this->security->log('order_honeypot', 'fraud', [
                'message' => 'Honeypot filled',
                'product_slug' => $slug,
            ]);

            return [
                'success' => false,
                'message' => $this->locale->t('order_error_server'),
                'status' => 422,
            ];
        }

        $locale = $this->locale->current();
        $product = $this->catalog->findProduct($slug, $locale);

        if ($product === null) {
            $this->logValidation('order_validation', 'Unknown product', $slug, $phone);

            return [
                'success' => false,
                'message' => $this->locale->t('order_error_product'),
                'status' => 404,
            ];
        }

        $variant = $this->catalog->findVariant($product, $variantId);
        if ($variant === null) {
            $this->logValidation('order_validation', 'Invalid variant', $slug, $phone);

            return [
                'success' => false,
                'message' => $this->locale->t('order_error_variant'),
                'status' => 422,
            ];
        }

        if (($variant['active'] ?? true) === false) {
            $this->logValidation('order_validation', 'Inactive variant', $slug, $phone);

            return [
                'success' => false,
                'message' => $this->locale->t('order_error_variant_inactive'),
                'status' => 422,
            ];
        }

        if ($name === '' || mb_strlen($name) < 2) {
            $this->logValidation('order_validation', 'Invalid name', $slug, $phone);

            return [
                'success' => false,
                'message' => $this->locale->t('order_error_name'),
                'status' => 422,
            ];
        }

        if (mb_strlen($name) > 100) {
            $this->logValidation('order_validation', 'Name too long', $slug, $phone);

            return [
                'success' => false,
                'message' => $this->locale->t('order_error_name_length'),
                'status' => 422,
            ];
        }

        if (mb_strlen($comment) > 500) {
            $this->logValidation('order_validation', 'Comment too long', $slug, $phone);

            return [
                'success' => false,
                'message' => $this->locale->t('order_error_comment_length'),
                'status' => 422,
            ];
        }

        $phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($phoneDigits) < 10) {
            $this->logValidation('order_validation', 'Invalid phone', $slug, $phone);

            return [
                'success' => false,
                'message' => $this->locale->t('order_error_phone'),
                'status' => 422,
            ];
        }

        $order = [
            'id' => date('Ymd-His') . '-' . bin2hex(random_bytes(3)),
            'created_at' => date('c'),
            'locale' => $locale,
            'product_slug' => $slug,
            'product_name' => $product['name'] ?? '',
            'variant_id' => $variantId,
            'variant_name' => $variant['name'] ?? $variantId,
            'price' => $variant['price'] ?? $product['price'] ?? null,
            'price_currency' => $product['price_currency'] ?? 'UAH',
            'customer_name' => $name,
            'customer_phone' => $phone,
            'comment' => $comment,
            'status' => 'new',
        ];

        if (!$this->orders->save($order)) {
            Logger::error('Order save failed', ['order_id' => $order['id']]);
            $this->security->log('order_save_failed', 'suspect', [
                'message' => 'DB save failed',
                'product_slug' => $slug,
                'phone' => $phone,
            ]);

            return [
                'success' => false,
                'message' => $this->locale->t('order_error_server'),
                'status' => 500,
            ];
        }

        if (!$this->telegram->notifyNewOrder($order)) {
            Logger::error('Telegram notification failed', ['order_id' => $order['id']]);
        }

        $this->security->log('order_success', 'ok', [
            'order_id' => $order['id'],
            'product_slug' => $slug,
            'phone' => $phone,
        ]);

        return [
            'success' => true,
            'message' => $this->locale->t('order_success'),
            'order_id' => $order['id'],
            'status' => 200,
        ];
    }

    private function logValidation(string $type, string $message, string $slug, string $phone): void
    {
        $this->security->log($type, 'suspect', [
            'message' => $message,
            'product_slug' => $slug,
            'phone' => $phone,
        ]);
    }
}

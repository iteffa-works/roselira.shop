<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Core\Request;
use Flowaxy\Repositories\Contracts\OrderRepositoryInterface;

final class OrderService
{
    /** @param list<string> $statuses */
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CatalogService $catalog,
        private readonly LocaleService $locale,
        private readonly TelegramNotificationService $telegram,
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
            return [
                'success' => false,
                'message' => $this->locale->t('order_error_server'),
                'status' => 422,
            ];
        }

        $locale = $this->locale->current();
        $product = $this->catalog->findProduct($slug, $locale);

        if ($product === null) {
            return [
                'success' => false,
                'message' => $this->locale->t('order_error_product'),
                'status' => 404,
            ];
        }

        $variant = $this->catalog->findVariant($product, $variantId);
        if ($variant === null) {
            return [
                'success' => false,
                'message' => $this->locale->t('order_error_variant'),
                'status' => 422,
            ];
        }

        if ($name === '' || mb_strlen($name) < 2) {
            return [
                'success' => false,
                'message' => $this->locale->t('order_error_name'),
                'status' => 422,
            ];
        }

        $phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($phoneDigits) < 10) {
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
            return [
                'success' => false,
                'message' => $this->locale->t('order_error_server'),
                'status' => 500,
            ];
        }

        $this->telegram->notifyNewOrder($order);

        return [
            'success' => true,
            'message' => $this->locale->t('order_success'),
            'order_id' => $order['id'],
            'status' => 200,
        ];
    }
}

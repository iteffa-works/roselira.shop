<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Sqlite;

use Flowaxy\Repositories\Contracts\OrderRepositoryInterface;
use Flowaxy\Support\JsonCodec;

final class SqliteOrderRepository implements OrderRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function all(): array
    {
        $rows = $this->connection->pdo()->query('SELECT data FROM orders ORDER BY created_at DESC')->fetchAll();
        $orders = [];

        foreach ($rows as $row) {
            $order = JsonCodec::decode((string) $row['data']);
            if ($order !== []) {
                $orders[] = $order;
            }
        }

        return $orders;
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT data FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $order = JsonCodec::decode((string) $row['data']);

        return $order !== [] ? $order : null;
    }

    public function save(array $order): bool
    {
        $id = (string) ($order['id'] ?? '');
        if ($id === '') {
            return false;
        }

        try {
            Connection::persistOrder($this->connection->pdo(), $order);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteById(string $id): bool
    {
        if ($id === '') {
            return false;
        }

        $stmt = $this->connection->pdo()->prepare('DELETE FROM orders WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function deleteByStatuses(array $statuses): int
    {
        $statuses = array_values(array_filter($statuses, static fn(string $s): bool => $s !== ''));
        if ($statuses === []) {
            return 0;
        }

        $placeholders = implode(', ', array_fill(0, count($statuses), '?'));
        $stmt = $this->connection->pdo()->prepare("DELETE FROM orders WHERE status IN ({$placeholders})");
        $stmt->execute($statuses);

        return $stmt->rowCount();
    }

    public function deleteAll(): int
    {
        $count = (int) $this->connection->pdo()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $this->connection->pdo()->exec('DELETE FROM orders');

        return $count;
    }

    public function countByStatus(): array
    {
        $rows = $this->connection->pdo()->query('SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status')->fetchAll();
        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row['status']] = (int) $row['cnt'];
        }

        return $counts;
    }
}

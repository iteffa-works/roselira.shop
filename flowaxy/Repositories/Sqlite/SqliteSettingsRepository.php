<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Sqlite;

use Flowaxy\Repositories\Contracts\SettingsRepositoryInterface;

final class SqliteSettingsRepository implements SettingsRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->connection->pdo()->prepare('SELECT value FROM settings WHERE key = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch();

        if ($row === false) {
            return $default;
        }

        return (string) $row['value'];
    }

    public function setMany(array $values): bool
    {
        if ($values === []) {
            return true;
        }

        $pdo = $this->connection->pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(<<<'SQL'
                INSERT INTO settings (key, value)
                VALUES (:key, :value)
                ON CONFLICT(key) DO UPDATE SET value = excluded.value
                SQL);

            foreach ($values as $key => $value) {
                $stmt->execute([
                    'key' => (string) $key,
                    'value' => (string) ($value ?? ''),
                ]);
            }

            $pdo->commit();

            return true;
        } catch (\Throwable) {
            $pdo->rollBack();

            return false;
        }
    }
}

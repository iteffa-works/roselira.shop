<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Sqlite;

use PDO;
use Flowaxy\Support\JsonCodec;

final class Connection
{
    private ?PDO $pdo = null;
    private bool $dumpRestored = false;

    public function __construct(
        private readonly string $dbPath,
        private readonly string $dumpPath,
    ) {
    }

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $dir = dirname($this->dbPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create data directory: ' . $dir);
        }

        $this->pdo = new PDO('sqlite:' . $this->dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec('PRAGMA journal_mode = WAL');
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->initSchema($this->pdo);

        return $this->pdo;
    }

    public function restoreFromDumpIfEmpty(): void
    {
        if ($this->dumpRestored) {
            return;
        }

        $this->dumpRestored = true;

        $pdo = $this->pdo();
        $productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

        if ($productCount > 0 || !is_readable($this->dumpPath)) {
            return;
        }

        $sql = (string) file_get_contents($this->dumpPath);
        if ($sql === '') {
            return;
        }

        $pdo->exec('PRAGMA foreign_keys = OFF');
        $pdo->exec($sql);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    private function initSchema(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS meta (
                key TEXT PRIMARY KEY NOT NULL,
                value TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS catalog_groups (
                id TEXT PRIMARY KEY NOT NULL,
                sort_order INTEGER NOT NULL DEFAULT 999
            );

            CREATE TABLE IF NOT EXISTS products (
                slug TEXT PRIMARY KEY NOT NULL,
                data TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS locale_strings (
                locale TEXT NOT NULL,
                key TEXT NOT NULL,
                value TEXT NOT NULL,
                PRIMARY KEY (locale, key)
            );

            CREATE TABLE IF NOT EXISTS orders (
                id TEXT PRIMARY KEY NOT NULL,
                created_at TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'new',
                data TEXT NOT NULL
            );

            CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at DESC);
            CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);

            CREATE TABLE IF NOT EXISTS admin_users (
                id INTEGER PRIMARY KEY CHECK (id = 1),
                username TEXT NOT NULL,
                password_hash TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY NOT NULL,
                value TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS security_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL,
                event_type TEXT NOT NULL,
                verdict TEXT NOT NULL,
                ip TEXT NOT NULL,
                user_agent TEXT NOT NULL DEFAULT '',
                path TEXT NOT NULL DEFAULT '',
                method TEXT NOT NULL DEFAULT '',
                meta TEXT NOT NULL DEFAULT '{}'
            );

            CREATE INDEX IF NOT EXISTS idx_security_events_created_at ON security_events(created_at DESC);
            CREATE INDEX IF NOT EXISTS idx_security_events_ip ON security_events(ip);
            CREATE INDEX IF NOT EXISTS idx_security_events_verdict ON security_events(verdict);

            CREATE TABLE IF NOT EXISTS rate_limit_hits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                scope TEXT NOT NULL,
                ip TEXT NOT NULL,
                created_at TEXT NOT NULL
            );

            CREATE INDEX IF NOT EXISTS idx_rate_limit_scope_ip ON rate_limit_hits(scope, ip, created_at DESC);
            SQL);
    }

    public static function persistCatalog(PDO $pdo, array $catalog): void
    {
        $pdo->beginTransaction();

        try {
            $pdo->exec('DELETE FROM meta');
            $pdo->exec('DELETE FROM catalog_groups');
            $pdo->exec('DELETE FROM products');

            $metaStmt = $pdo->prepare('INSERT INTO meta (key, value) VALUES (:key, :value)');
            foreach ($catalog['meta'] ?? [] as $key => $value) {
                $metaStmt->execute([
                    'key' => (string) $key,
                    'value' => JsonCodec::encode($value),
                ]);
            }

            $groupStmt = $pdo->prepare('INSERT INTO catalog_groups (id, sort_order) VALUES (:id, :sort_order)');
            foreach ($catalog['groups'] ?? [] as $groupId => $group) {
                if (!is_array($group)) {
                    continue;
                }

                $groupStmt->execute([
                    'id' => (string) $groupId,
                    'sort_order' => (int) ($group['order'] ?? 999),
                ]);
            }

            $productStmt = $pdo->prepare('INSERT INTO products (slug, data) VALUES (:slug, :data)');
            foreach ($catalog['products'] ?? [] as $slug => $product) {
                if (!is_array($product)) {
                    continue;
                }

                $productStmt->execute([
                    'slug' => (string) $slug,
                    'data' => JsonCodec::encode($product),
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function persistLocaleStrings(PDO $pdo, string $locale, array $translations): void
    {
        $pdo->beginTransaction();

        try {
            $delete = $pdo->prepare('DELETE FROM locale_strings WHERE locale = :locale');
            $delete->execute(['locale' => $locale]);

            $insert = $pdo->prepare('INSERT INTO locale_strings (locale, key, value) VALUES (:locale, :key, :value)');
            foreach ($translations as $key => $value) {
                $insert->execute([
                    'locale' => $locale,
                    'key' => (string) $key,
                    'value' => (string) $value,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function persistOrder(PDO $pdo, array $order): void
    {
        $id = (string) ($order['id'] ?? '');
        if ($id === '') {
            throw new \InvalidArgumentException('Order id is required');
        }

        $stmt = $pdo->prepare(<<<'SQL'
            INSERT INTO orders (id, created_at, status, data)
            VALUES (:id, :created_at, :status, :data)
            ON CONFLICT(id) DO UPDATE SET
                created_at = excluded.created_at,
                status = excluded.status,
                data = excluded.data
            SQL);

        $stmt->execute([
            'id' => $id,
            'created_at' => (string) ($order['created_at'] ?? date('c')),
            'status' => (string) ($order['status'] ?? 'new'),
            'data' => JsonCodec::encode($order),
        ]);
    }

    /** @return array{orders: int, products: int, locale_strings: int, security_events: int} */
    public function tableCounts(): array
    {
        $pdo = $this->pdo();

        return [
            'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'locale_strings' => (int) $pdo->query('SELECT COUNT(*) FROM locale_strings')->fetchColumn(),
            'security_events' => (int) $pdo->query('SELECT COUNT(*) FROM security_events')->fetchColumn(),
        ];
    }

    public function dbFileSize(): int
    {
        return is_file($this->dbPath) ? (int) filesize($this->dbPath) : 0;
    }

    public function vacuum(): void
    {
        $this->pdo()->exec('VACUUM');
    }
}

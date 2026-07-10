<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Sqlite;

use Flowaxy\Repositories\Contracts\CatalogRepositoryInterface;
use Flowaxy\Support\JsonCodec;

final class SqliteCatalogRepository implements CatalogRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function load(): array
    {
        $pdo = $this->connection->pdo();
        $catalog = [
            'meta' => [],
            'groups' => [],
            'categories' => [],
            'products' => [],
        ];

        $metaRows = $pdo->query('SELECT key, value FROM meta')->fetchAll();
        foreach ($metaRows as $row) {
            $catalog['meta'][(string) $row['key']] = JsonCodec::decode((string) $row['value']);
        }

        $groupRows = $pdo->query('SELECT id, sort_order FROM catalog_groups ORDER BY sort_order, id')->fetchAll();
        foreach ($groupRows as $row) {
            $catalog['groups'][(string) $row['id']] = [
                'order' => (int) $row['sort_order'],
            ];
        }

        try {
            $categoryRows = $pdo->query(
                'SELECT id, sort_order, data FROM catalog_categories ORDER BY sort_order, id'
            )->fetchAll();
        } catch (\Throwable) {
            $categoryRows = [];
        }

        foreach ($categoryRows as $row) {
            $data = JsonCodec::decode((string) $row['data']);
            if (!is_array($data)) {
                $data = [];
            }

            $catalog['categories'][(string) $row['id']] = $data + [
                'order' => (int) $row['sort_order'],
            ];
        }

        $productRows = $pdo->query('SELECT slug, data FROM products ORDER BY slug')->fetchAll();
        foreach ($productRows as $row) {
            $catalog['products'][(string) $row['slug']] = JsonCodec::decode((string) $row['data']);
        }

        return $catalog;
    }

    public function save(array $catalog): bool
    {
        try {
            Connection::persistCatalog($this->connection->pdo(), $catalog);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Sqlite;

use Flowaxy\Repositories\Contracts\ProductRatingRepositoryInterface;

final class SqliteProductRatingRepository implements ProductRatingRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /** @return array{created: bool} */
    public function upsertVote(string $productSlug, int $rating, string $voterHash): array
    {
        if ($productSlug === '' || $voterHash === '') {
            return ['created' => false];
        }

        $rating = max(1, min(5, $rating));
        $pdo = $this->connection->pdo();
        $existing = $this->findUserVote($productSlug, $voterHash);
        $created = $existing === null;

        if ($created) {
            $stmt = $pdo->prepare(<<<'SQL'
                INSERT INTO product_ratings (product_slug, rating, voter_hash, created_at)
                VALUES (:product_slug, :rating, :voter_hash, :created_at)
                SQL);
        } else {
            $stmt = $pdo->prepare(<<<'SQL'
                UPDATE product_ratings
                SET rating = :rating, created_at = :created_at
                WHERE product_slug = :product_slug AND voter_hash = :voter_hash
                SQL);
        }

        $stmt->execute([
            'product_slug' => $productSlug,
            'rating' => $rating,
            'voter_hash' => $voterHash,
            'created_at' => date('c'),
        ]);

        return ['created' => $created];
    }

    public function findUserVote(string $productSlug, string $voterHash): ?int
    {
        if ($productSlug === '' || $voterHash === '') {
            return null;
        }

        $stmt = $this->connection->pdo()->prepare(<<<'SQL'
            SELECT rating
            FROM product_ratings
            WHERE product_slug = :product_slug AND voter_hash = :voter_hash
            LIMIT 1
            SQL);
        $stmt->execute([
            'product_slug' => $productSlug,
            'voter_hash' => $voterHash,
        ]);
        $value = $stmt->fetchColumn();

        return $value === false ? null : (int) $value;
    }

    /** @return array{count: int, sum: int} */
    public function userVoteStats(string $productSlug): array
    {
        if ($productSlug === '') {
            return ['count' => 0, 'sum' => 0];
        }

        $stmt = $this->connection->pdo()->prepare(<<<'SQL'
            SELECT COUNT(*) AS cnt, COALESCE(SUM(rating), 0) AS total
            FROM product_ratings
            WHERE product_slug = :product_slug
            SQL);
        $stmt->execute(['product_slug' => $productSlug]);
        $row = $stmt->fetch();

        return [
            'count' => (int) ($row['cnt'] ?? 0),
            'sum' => (int) ($row['total'] ?? 0),
        ];
    }
}

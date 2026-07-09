<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Contracts;

interface ProductRatingRepositoryInterface
{
    /** @return array{created: bool} */
    public function upsertVote(string $productSlug, int $rating, string $voterHash): array;

    public function findUserVote(string $productSlug, string $voterHash): ?int;

    /** @return array{count: int, sum: int} */
    public function userVoteStats(string $productSlug): array;
}

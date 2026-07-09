<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Core\Request;
use Flowaxy\Repositories\Contracts\ProductRatingRepositoryInterface;
use Flowaxy\Support\RequestContext;

final class ProductRatingService
{
    public function __construct(
        private readonly ProductRatingRepositoryInterface $ratings,
        private readonly CatalogService $catalog,
    ) {
    }

    /** @return array{rating: float, reviews_count: int, user_vote: ?int} */
    public function resolveForProduct(string $slug, array $product): array
    {
        $baselineRating = (float) ($product['rating'] ?? 0);
        $baselineCount = (int) ($product['reviews_count'] ?? 0);
        $userStats = $this->ratings->userVoteStats($slug);
        $voterHash = $this->voterHash();

        $baselineScore = $baselineRating * $baselineCount;
        $totalCount = $baselineCount + $userStats['count'];
        $totalScore = $baselineScore + $userStats['sum'];

        return [
            'rating' => $totalCount > 0 ? round($totalScore / $totalCount, 1) : 0.0,
            'reviews_count' => $totalCount,
            'user_vote' => $this->ratings->findUserVote($slug, $voterHash),
        ];
    }

    /** @return array{success: bool, message: string, rating?: float, reviews_count?: int, user_vote?: int, count_increased?: bool, status: int} */
    public function vote(Request $request, string $slug): array
    {
        $slug = trim($slug);
        if ($slug === '' || $this->catalog->findProduct($slug) === null) {
            return [
                'success' => false,
                'message' => t('rating_error_product'),
                'status' => 404,
            ];
        }

        $rating = (int) $request->post('rating', 0);
        if ($rating < 1 || $rating > 5) {
            return [
                'success' => false,
                'message' => t('rating_error_value'),
                'status' => 422,
            ];
        }

        $voterHash = $this->voterHash();
        $upsert = $this->ratings->upsertVote($slug, $rating, $voterHash);
        if ($this->ratings->findUserVote($slug, $voterHash) === null) {
            return [
                'success' => false,
                'message' => t('rating_error_server'),
                'status' => 500,
            ];
        }

        $product = $this->catalog->findProduct($slug);
        if ($product === null) {
            return [
                'success' => false,
                'message' => t('rating_error_product'),
                'status' => 404,
            ];
        }

        $resolved = $this->resolveForProduct($slug, $product);

        return [
            'success' => true,
            'message' => t('rating_success'),
            'rating' => $resolved['rating'],
            'reviews_count' => $resolved['reviews_count'],
            'user_vote' => $resolved['user_vote'],
            'count_increased' => $upsert['created'],
            'status' => 200,
        ];
    }

    private function voterHash(): string
    {
        return hash('sha256', RequestContext::clientIp() . '|' . RequestContext::userAgent());
    }
}

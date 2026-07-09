<?php

declare(strict_types=1);

namespace Flowaxy\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Services\ProductRatingService;

final class RatingController
{
    public function __construct(
        private readonly ProductRatingService $ratings,
    ) {
    }

    public function store(Request $request): Response
    {
        if (!$request->isPost()) {
            return Response::json(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $slug = trim((string) $request->post('product_slug', ''));
        $result = $this->ratings->vote($request, $slug);

        $payload = [
            'success' => $result['success'],
            'message' => $result['message'],
        ];

        if (isset($result['rating'])) {
            $payload['rating'] = $result['rating'];
        }

        if (isset($result['reviews_count'])) {
            $payload['reviews_count'] = $result['reviews_count'];
        }

        if (array_key_exists('user_vote', $result)) {
            $payload['user_vote'] = $result['user_vote'];
        }

        if (isset($result['count_increased'])) {
            $payload['count_increased'] = $result['count_increased'];
        }

        return Response::json($payload, $result['status']);
    }
}

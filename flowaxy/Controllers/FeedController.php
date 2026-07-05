<?php

declare(strict_types=1);

namespace Flowaxy\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Services\ProductFeedService;

final class FeedController
{
    public function __construct(private readonly ProductFeedService $feeds)
    {
    }

    public function meta(Request $request): Response
    {
        return $this->respond($request, ProductFeedService::CHANNEL_META);
    }

    public function google(Request $request): Response
    {
        return $this->respond($request, ProductFeedService::CHANNEL_GOOGLE);
    }

    private function respond(Request $request, string $channel): Response
    {
        if ($denied = $this->authorize($request)) {
            return $denied;
        }

        return Response::xml($this->feeds->buildXml($channel));
    }

    private function authorize(Request $request): ?Response
    {
        $secret = (string) (app_config()['feed_secret'] ?? '');
        if ($secret === '') {
            return null;
        }

        if ($request->query('token') !== $secret) {
            return Response::html('Forbidden', 403);
        }

        return null;
    }
}

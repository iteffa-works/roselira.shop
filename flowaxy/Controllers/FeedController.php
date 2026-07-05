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
        if ($denied = $this->authorize($request)) {
            return $denied;
        }

        return Response::xml($this->feeds->buildMetaXml());
    }

    public function google(Request $request): Response
    {
        if ($denied = $this->authorize($request)) {
            return $denied;
        }

        return Response::xml($this->feeds->buildGoogleXml());
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

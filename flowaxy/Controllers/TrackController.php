<?php

declare(strict_types=1);

namespace Flowaxy\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Services\VisitorAnalyticsService;

final class TrackController
{
    public function __construct(
        private readonly VisitorAnalyticsService $analytics,
    ) {
    }

    public function collect(Request $request): Response
    {
        if (!$request->isPost()) {
            return Response::json(['ok' => false], 405);
        }

        $raw = (string) file_get_contents('php://input');
        if ($raw === '') {
            return Response::json(['ok' => false], 400);
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            return Response::json(['ok' => false], 400);
        }

        $ok = $this->analytics->collect($payload);

        return Response::json(['ok' => $ok], $ok ? 200 : 422);
    }
}

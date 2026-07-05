<?php

declare(strict_types=1);

namespace Flowaxy\Controllers;

use Flowaxy\Core\Request;
use Flowaxy\Core\Response;
use Flowaxy\Services\LocaleService;
use Flowaxy\Services\OrderService;
use Flowaxy\Services\RecaptchaService;
use Flowaxy\Services\SecurityLogService;
use Flowaxy\Support\AppState;
use Flowaxy\Support\OrderRateLimiter;

final class OrderController
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly OrderRateLimiter $rateLimiter,
        private readonly SecurityLogService $security,
        private readonly RecaptchaService $recaptcha,
    ) {
    }

    public function store(Request $request): Response
    {
        if (!$request->isPost()) {
            return Response::json(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        if ($this->rateLimiter->isLimited()) {
            $this->security->log('order_rate_limited', 'fraud', [
                'message' => 'IP rate limit',
            ]);

            return Response::json([
                'success' => false,
                'message' => AppState::$locale->t('order_error_rate_limit'),
            ], 429);
        }

        if (!$this->recaptcha->verifyRequest($request)) {
            $this->security->log('order_captcha_failed', 'fraud', [
                'message' => 'reCAPTCHA failed',
            ]);

            return Response::json([
                'success' => false,
                'message' => AppState::$locale->t('order_error_captcha'),
            ], 422);
        }

        $result = $this->orders->createFromRequest($request);

        if ($result['success']) {
            $this->rateLimiter->hit();
        }

        $payload = [
            'success' => $result['success'],
            'message' => $result['message'],
        ];

        if (!empty($result['order_id'])) {
            $payload['order_id'] = $result['order_id'];
        }

        return Response::json($payload, $result['status']);
    }
}

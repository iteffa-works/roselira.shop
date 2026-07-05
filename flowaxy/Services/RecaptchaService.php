<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Core\Request;
use Flowaxy\Support\RequestContext;

final class RecaptchaService
{
    public function __construct(
        private readonly string $siteKey,
        private readonly string $secretKey,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->siteKey !== '' && $this->secretKey !== '';
    }

    public function siteKey(): string
    {
        return $this->siteKey;
    }

    public function tokenFromRequest(Request $request): string
    {
        return trim((string) $request->post('g-recaptcha-response', ''));
    }

    public function verifyRequest(Request $request): bool
    {
        return $this->verify($this->tokenFromRequest($request));
    }

    public function verify(string $token): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if ($token === '') {
            return false;
        }

        $payload = http_build_query([
            'secret' => $this->secretKey,
            'response' => $token,
            'remoteip' => RequestContext::clientIp(),
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 8,
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        if ($raw === false || $raw === '') {
            return false;
        }

        $data = json_decode($raw, true);

        return is_array($data) && ($data['success'] ?? false) === true;
    }
}

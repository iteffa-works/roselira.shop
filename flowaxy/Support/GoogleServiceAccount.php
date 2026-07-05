<?php

declare(strict_types=1);

namespace Flowaxy\Support;

final class GoogleServiceAccount
{
    /** @var array<string, string|null> */
    private static array $tokenCache = [];

    public static function accessToken(string $jsonPath): ?string
    {
        $cacheKey = md5($jsonPath);
        $cached = self::$tokenCache[$cacheKey] ?? null;
        if ($cached !== null && ($cached['expires_at'] ?? 0) > time() + 60) {
            return $cached['token'];
        }

        if (!is_readable($jsonPath)) {
            return null;
        }

        $raw = file_get_contents($jsonPath);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['client_email']) || empty($data['private_key'])) {
            return null;
        }

        $jwt = self::buildJwt((string) $data['client_email'], (string) $data['private_key']);
        if ($jwt === null) {
            return null;
        }

        $response = self::postForm('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response === null || empty($response['access_token'])) {
            return null;
        }

        $ttl = max(300, (int) ($response['expires_in'] ?? 3600));
        self::$tokenCache[$cacheKey] = [
            'token' => (string) $response['access_token'],
            'expires_at' => time() + $ttl,
        ];

        return (string) $response['access_token'];
    }

    private static function buildJwt(string $email, string $privateKey): ?string
    {
        $now = time();
        $header = self::base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = self::base64Url(json_encode([
            'iss' => $email,
            'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $input = $header . '.' . $payload;
        $signature = '';
        if (!openssl_sign($input, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        return $input . '.' . self::base64Url($signature);
    }

    /** @param array<string, string> $fields */
    private static function postForm(string $url, array $fields): ?array
    {
        $body = http_build_query($fields);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

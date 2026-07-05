<?php

declare(strict_types=1);

use Flowaxy\Services\LocaleService;
use Flowaxy\Support\AppState;

function flowaxy_set_locale(LocaleService $locale): void
{
    AppState::$locale = $locale;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    return '/' . ltrim($path, '/');
}

function app_url(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $configured = (string) (AppState::$config['app_url'] ?? '');
    if ($configured !== '') {
        $cached = $configured;

        return $cached;
    }

    $scheme = request_is_https() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $cached = $scheme . '://' . $host;

    return $cached;
}

function absolute_url(string $path): string
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    return rtrim(app_url(), '/') . asset($path);
}

function request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

/** @return array<string, mixed> */
function app_config(): array
{
    return AppState::$config;
}

function formatPrice(float $price, string $currency = 'UAH'): string
{
    $symbols = ['USD' => '$', 'EUR' => '€', 'UAH' => '₴'];
    $symbol = $symbols[$currency] ?? $currency . ' ';

    if (in_array($currency, ['USD', 'EUR'], true)) {
        return $symbol . number_format($price, 2, '.', '');
    }

    return number_format($price, 0, '.', ' ') . ' ' . $symbol;
}

function renderStars(float $rating, int $max = 5): string
{
    $rating = max(0, min($max, $rating));
    $fullStars = (int) floor($rating);
    $hasHalf = ($rating - $fullStars) >= 0.25 && ($rating - $fullStars) < 0.75;
    $emptyStars = $max - $fullStars - ($hasHalf ? 1 : 0);

    if (($rating - $fullStars) >= 0.75) {
        $emptyStars = max(0, $emptyStars - 1);
        $fullStars++;
        $hasHalf = false;
    }

    $html = '<span class="stars" aria-label="' . e(t('rating_of', ['rating' => number_format($rating, 1), 'max' => (string) $max])) . '">';

    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<span class="star star--full" aria-hidden="true">★</span>';
    }

    if ($hasHalf) {
        $html .= '<span class="star star--half" aria-hidden="true">★</span>';
    }

    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<span class="star star--empty" aria-hidden="true">★</span>';
    }

    $html .= '</span>';

    return $html;
}

function t(string $key, array $replace = []): string
{
    return AppState::$locale->t($key, $replace);
}

function currentLocale(): string
{
    return AppState::$locale->current();
}

function langUrl(string $lang): string
{
    return AppState::$locale->langUrl($lang);
}

function admin_url(string $path = '', array $query = []): string
{
    $url = '/admin';
    if ($path !== '') {
        $url .= '/' . ltrim($path, '/');
    }
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

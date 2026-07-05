<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Repositories\Contracts\LocaleRepositoryInterface;

final class LocaleService
{
    /** @var array<string, string> */
    private array $translations = [];

    /** @var list<string> */
    private array $publicLocales;

    /** @var list<string> */
    private array $editableLocales;

    private string $fallback;
    private string $default;
    private string $cookie;
    private string $current;

    /** @param list<string> $publicLocales @param list<string> $editableLocales */
    public function __construct(
        private readonly LocaleRepositoryInterface $localeRepository,
        array $publicLocales,
        string $fallback,
        string $default,
        string $cookie,
        array $editableLocales,
    ) {
        $this->publicLocales = $publicLocales;
        $this->editableLocales = $editableLocales;
        $this->fallback = $fallback;
        $this->default = $default;
        $this->cookie = $cookie;
        $this->current = $default;
    }

    /** @return list<string> */
    public function publicLocales(): array
    {
        return $this->publicLocales;
    }

    /** @return list<string> */
    public function editableLocales(): array
    {
        return $this->editableLocales;
    }

    public function fallback(): string
    {
        return $this->fallback;
    }

    public function current(): string
    {
        return $this->current;
    }

    public function resolveLanguageSwitch(): ?string
    {
        if (!isset($_GET['lang']) || !in_array($_GET['lang'], $this->publicLocales, true)) {
            return null;
        }

        setcookie(
            $this->cookie,
            $_GET['lang'],
            $this->cookieOptions(),
        );
        $_COOKIE[$this->cookie] = $_GET['lang'];

        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    }

    public function boot(): void
    {
        $hasCookie = isset($_COOKIE[$this->cookie]);
        $locale = $hasCookie ? (string) $_COOKIE[$this->cookie] : $this->detectFromRegion();

        if (!in_array($locale, $this->publicLocales, true)) {
            $locale = $this->default;
        }

        if (!$hasCookie) {
            $this->persistLocaleCookie($locale);
        }

        $this->current = $locale;
        $this->loadTranslations($locale);
    }

    /** @return array<string, string> */
    public function localeLabels(): array
    {
        return [
            'uk' => 'UA',
            'ru' => 'RU',
        ];
    }

    public function localeLabel(string $locale): string
    {
        return $this->localeLabels()[$locale] ?? strtoupper($locale);
    }

    public function t(string $key, array $replace = []): string
    {
        $value = $this->translations[$key] ?? $key;

        foreach ($replace as $name => $replacement) {
            $value = str_replace(':' . $name, (string) $replacement, $value);
        }

        return $value;
    }

    public function langUrl(string $lang): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return $path . '?lang=' . $lang;
    }

    /** @return array<string, string> */
    public function loadStrings(string $locale): array
    {
        return $this->localeRepository->loadStrings($locale);
    }

    /** @param array<string, string> $translations */
    public function saveStrings(string $locale, array $translations): bool
    {
        return $this->localeRepository->saveStrings($locale, $translations);
    }

    /** @return array<string, string> */
    public function adminLocaleLabels(): array
    {
        return [
            'uk' => 'Українська',
            'ru' => 'Російська',
        ];
    }

    public function adminLocaleLabel(string $locale): string
    {
        return $this->adminLocaleLabels()[$locale] ?? strtoupper($locale);
    }

    private function loadTranslations(string $locale): void
    {
        $strings = $this->localeRepository->loadStrings($this->fallback);
        if ($locale !== $this->fallback) {
            $strings = array_merge($strings, $this->localeRepository->loadStrings($locale));
        }

        $this->translations = $strings;
    }

    private function detectFromRegion(): string
    {
        $country = strtoupper((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''));

        if ($country === 'UA') {
            return 'uk';
        }

        if (in_array($country, ['RU', 'BY', 'KZ'], true)) {
            return 'ru';
        }

        return $this->detectFromAcceptLanguage() ?? $this->default;
    }

    private function detectFromAcceptLanguage(): ?string
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        if ($accept === '') {
            return null;
        }

        $preferences = [];

        foreach (explode(',', $accept) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $segments = explode(';', $part);
            $tag = strtolower(trim($segments[0]));
            $quality = 1.0;

            if (isset($segments[1]) && preg_match('/q=([0-9.]+)/', $segments[1], $matches)) {
                $quality = (float) $matches[1];
            }

            $preferences[] = ['tag' => $tag, 'quality' => $quality];
        }

        usort($preferences, static fn(array $a, array $b): int => $b['quality'] <=> $a['quality']);

        foreach ($preferences as $preference) {
            $tag = $preference['tag'];
            $primary = explode('-', $tag)[0];

            if ($tag === 'uk' || $tag === 'uk-ua' || $primary === 'uk') {
                return 'uk';
            }

            if ($tag === 'ru' || $tag === 'ru-ru' || $primary === 'ru') {
                return 'ru';
            }
        }

        return null;
    }

    private function persistLocaleCookie(string $locale): void
    {
        setcookie($this->cookie, $locale, $this->cookieOptions());
        $_COOKIE[$this->cookie] = $locale;
    }

    /** @return array{expires: int, path: string, samesite: string} */
    private function cookieOptions(): array
    {
        return [
            'expires' => time() + 365 * 24 * 3600,
            'path' => '/',
            'samesite' => 'Lax',
        ];
    }
}

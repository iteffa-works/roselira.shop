<?php

declare(strict_types=1);

namespace Flowaxy\Services;

use Flowaxy\Repositories\Contracts\SettingsRepositoryInterface;
use Flowaxy\Support\JsonCodec;
use Flowaxy\Support\LegalContent;
use Flowaxy\Support\LegalPages;

final class LegalPageService
{
    /** @var list<string> */
    public const PAGES = LegalPages::KEYS;

    /** @var list<string> */
    public const LOCALES = ['uk', 'ru'];

    public function __construct(private readonly SettingsRepositoryInterface $settings)
    {
    }

    /** @return array<string, string> */
    public function pageLabels(): array
    {
        return [
            'privacy' => 'Конфіденційність',
            'terms' => 'Умови',
            'delivery' => 'Доставка',
        ];
    }

    /** @return list<array{heading?: string, paragraphs: list<string>}> */
    public function getSections(string $page, string $locale): array
    {
        if (!$this->isValidPage($page) || !$this->isValidLocale($locale)) {
            return [];
        }

        $raw = $this->settings->get($this->storageKey($page, $locale));
        if ($raw !== null && $raw !== '') {
            $decoded = JsonCodec::decode($raw);
            if (is_array($decoded)) {
                $normalized = $this->normalizeSections($decoded);
                if ($normalized !== []) {
                    return $normalized;
                }
            }
        }

        return LegalContent::sections($page, $locale);
    }

    /** @param list<array{heading?: string, paragraphs?: list<string>|string}> $sections */
    public function saveSections(string $page, string $locale, array $sections): bool
    {
        if (!$this->isValidPage($page) || !$this->isValidLocale($locale)) {
            return false;
        }

        $normalized = $this->normalizeSections($sections);
        if ($normalized === []) {
            return false;
        }

        return $this->settings->setMany([
            $this->storageKey($page, $locale) => JsonCodec::encode($normalized),
        ]);
    }

    public function resetSections(string $page, string $locale): bool
    {
        if (!$this->isValidPage($page) || !$this->isValidLocale($locale)) {
            return false;
        }

        return $this->settings->setMany([
            $this->storageKey($page, $locale) => '',
        ]);
    }

    public function isValidPage(string $page): bool
    {
        return in_array($page, self::PAGES, true);
    }

    public function isValidLocale(string $locale): bool
    {
        return in_array($locale, self::LOCALES, true);
    }

    /** @return list<array{heading?: string, paragraphs: list<string>}> */
    public function defaultSections(string $page, string $locale): array
    {
        return LegalContent::sections($page, $locale);
    }

    private function storageKey(string $page, string $locale): string
    {
        return 'legal_page_' . $page . '_' . $locale;
    }

    /** @param list<mixed> $sections */
    private function normalizeSections(array $sections): array
    {
        $result = [];

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $heading = trim((string) ($section['heading'] ?? ''));
            $paragraphsRaw = $section['paragraphs'] ?? [];
            $paragraphs = [];

            if (is_string($paragraphsRaw)) {
                $lines = preg_split('/\r\n|\r|\n/', $paragraphsRaw) ?: [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $paragraphs[] = $line;
                    }
                }
            } elseif (is_array($paragraphsRaw)) {
                foreach ($paragraphsRaw as $paragraph) {
                    $paragraph = trim((string) $paragraph);
                    if ($paragraph !== '') {
                        $paragraphs[] = $paragraph;
                    }
                }
            }

            if ($heading === '' && $paragraphs === []) {
                continue;
            }

            $item = ['paragraphs' => $paragraphs];
            if ($heading !== '') {
                $item['heading'] = $heading;
            }

            $result[] = $item;
        }

        return $result;
    }
}

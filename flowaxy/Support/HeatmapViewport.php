<?php

declare(strict_types=1);

namespace Flowaxy\Support;

final class HeatmapViewport
{
    /** @var array<string, array{label: string, min: int, max: int, preview: int}> */
    public const PROFILES = [
        'mobile' => ['label' => 'Mobile', 'min' => 0, 'max' => 767, 'preview' => 390],
        'tablet' => ['label' => 'Tablet', 'min' => 768, 'max' => 1023, 'preview' => 768],
        'desktop' => ['label' => 'Desktop', 'min' => 1024, 'max' => 99999, 'preview' => 1280],
    ];

    public static function normalize(string $profile): string
    {
        return array_key_exists($profile, self::PROFILES) ? $profile : 'desktop';
    }

    /** @return array{label: string, min: int, max: int, preview: int} */
    public static function profile(string $profile): array
    {
        return self::PROFILES[self::normalize($profile)];
    }

    /** @return list<string> */
    public static function ids(): array
    {
        return array_keys(self::PROFILES);
    }
}

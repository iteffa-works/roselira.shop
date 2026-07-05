<?php

declare(strict_types=1);

namespace Flowaxy\Core;

final class Autoloader
{
    /** @var array<string, string> */
    private static array $prefixes = [];

    public static function register(string $baseDir, string $prefix, string $relativeDir): void
    {
        self::$prefixes[rtrim($prefix, '\\') . '\\'] = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR . trim($relativeDir, '/\\');
    }

    public static function load(string $class): void
    {
        foreach (self::$prefixes as $prefix => $baseDir) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
            $file = $baseDir . DIRECTORY_SEPARATOR . $relative;

            if (is_file($file)) {
                require $file;
            }

            return;
        }
    }

    public static function boot(string $baseDir): void
    {
        spl_autoload_register([self::class, 'load']);
        self::register($baseDir, 'Flowaxy\\', 'flowaxy');
    }
}

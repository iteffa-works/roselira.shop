<?php

declare(strict_types=1);

namespace Flowaxy\Support;

use RuntimeException;

final class JsonCodec
{
    public static function encode(mixed $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException('JSON encode failed');
        }

        return $json;
    }

    public static function decode(string $json): array
    {
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }
}

<?php

declare(strict_types=1);

namespace Flowaxy\Core;

final class Response
{
    /** @var array<string, string> */
    private static array $defaultHeaders = [];

    /** @param array<string, string> $headers */
    public static function setDefaultHeaders(array $headers): void
    {
        self::$defaultHeaders = $headers;
    }

    public function __construct(
        private string $content = '',
        private int $status = 200,
        /** @var array<string, string> */
        private array $headers = [],
    ) {
    }

    public static function html(string $content, int $status = 200): self
    {
        return new self($content, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function json(array $data, int $status = 200): self
    {
        return new self(
            (string) json_encode($data, JSON_UNESCAPED_UNICODE),
            $status,
            ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public static function xml(string $content, int $status = 200): self
    {
        return new self($content, $status, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach (array_merge(self::$defaultHeaders, $this->headers) as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
    }
}

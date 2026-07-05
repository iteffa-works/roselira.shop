<?php

declare(strict_types=1);

namespace Flowaxy\Core;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $post,
        private readonly array $server,
    ) {
    }

    public static function capture(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $_GET,
            $_POST,
            $_SERVER,
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function normalizedPath(): string
    {
        $path = $this->path;

        if (str_ends_with($path, '.php')) {
            $path = substr($path, 0, -4) ?: '/';
        }

        if (str_ends_with($path, '/index')) {
            $path = substr($path, 0, -6) ?: '/';
        }

        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }

        return $path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }
}

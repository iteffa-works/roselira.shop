<?php

declare(strict_types=1);

namespace Flowaxy\Core;

use Flowaxy\Controllers\HomeController;

final class Router
{
    /** @var list<array{methods: string[], pattern: string, handler: string}> */
    private array $routes = [];

    public function get(string $pattern, string $handler): void
    {
        $this->add(['GET'], $pattern, $handler);
    }

    public function post(string $pattern, string $handler): void
    {
        $this->add(['POST'], $pattern, $handler);
    }

    /** @param string[] $methods */
    private function add(array $methods, string $pattern, string $handler): void
    {
        $this->routes[] = [
            'methods' => array_map('strtoupper', $methods),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request, Container $container): Response
    {
        $path = $request->normalizedPath();
        $method = $request->method();

        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'], true)) {
                continue;
            }

            $params = $this->matchPath($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            $params['request'] = $request;

            return $this->toResponse($container->call($route['handler'], $params));
        }

        return $container->call(HomeController::class . '::notFoundResponse');
    }

    private function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        return Response::html('');
    }

    /** @return array<string, string>|null */
    private function matchPath(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = urldecode((string) $value);
            }
        }

        return $params;
    }
}

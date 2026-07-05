<?php

declare(strict_types=1);

namespace Flowaxy\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

final class Container
{
    /** @var array<string, Closure(self): mixed> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public function singleton(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = function (self $container) use ($factory, $abstract): mixed {
            if (!array_key_exists($abstract, $this->instances)) {
                $this->instances[$abstract] = $factory($container);
            }

            return $this->instances[$abstract];
        };
    }

    public function make(string $abstract): mixed
    {
        if (array_key_exists($abstract, $this->instances)) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]($this);
        }

        if (!class_exists($abstract)) {
            throw new RuntimeException("Cannot resolve: {$abstract}");
        }

        return $this->build($abstract);
    }

    public function call(callable|array|string $callable, array $parameters = []): mixed
    {
        if (is_string($callable) && str_contains($callable, '::')) {
            [$class, $method] = explode('::', $callable, 2);
            $callable = [$this->make($class), $method];
        }

        if (is_array($callable) && is_string($callable[0])) {
            $callable[0] = $this->make($callable[0]);
        }

        $ref = is_array($callable)
            ? new \ReflectionMethod($callable[0], $callable[1])
            : new \ReflectionFunction(Closure::fromCallable($callable));

        $args = [];

        foreach ($ref->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $parameters)) {
                $args[] = $parameters[$name];
                continue;
            }

            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->make($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException("Cannot resolve parameter \${$name} for {$ref->getName()}");
        }

        return $ref->invokeArgs(is_array($callable) ? $callable[0] : null, $args);
    }

    private function build(string $class): mixed
    {
        $ref = new ReflectionClass($class);

        if (!$ref->isInstantiable()) {
            throw new RuntimeException("Cannot instantiate: {$class}");
        }

        $constructor = $ref->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $args = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->make($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException("Cannot resolve constructor parameter for {$class}");
        }

        return $ref->newInstanceArgs($args);
    }
}

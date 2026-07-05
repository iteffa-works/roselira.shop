<?php

declare(strict_types=1);

namespace Flowaxy\Core;

final class Application
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
    ) {
    }

    public function run(): void
    {
        $this->router->dispatch(Request::capture(), $this->container)->send();
    }
}

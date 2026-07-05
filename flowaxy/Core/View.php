<?php

declare(strict_types=1);

namespace Flowaxy\Core;

use Flowaxy\Services\LocaleService;

final class View
{
    public function __construct(
        private readonly string $viewsPath,
        private readonly string $adminViewsPath,
        private readonly LocaleService $locale,
    ) {
    }

    public function render(string $viewFile, array $data = []): string
    {
        return $this->renderFrom($this->viewsPath, $viewFile, $data);
    }

    public function renderAdmin(string $viewFile, array $data = []): string
    {
        return $this->renderFrom($this->adminViewsPath, $viewFile, $data);
    }

    /** @param array<string, mixed> $data */
    private function renderFrom(string $basePath, string $viewFile, array $data): string
    {
        $data = array_merge(['publicLocales' => $this->locale->publicLocales()], $data);
        extract($data, EXTR_SKIP);

        ob_start();
        require $basePath . '/' . $viewFile . '.php';

        return (string) ob_get_clean();
    }
}

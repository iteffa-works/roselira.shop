<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Contracts;

interface AdminUserRepositoryInterface
{
    /** @return array{username: string, password_hash: string} */
    public function loadCredentials(): array;

    public function saveCredentials(string $username, string $passwordHash): bool;

    public function isConfigured(): bool;
}

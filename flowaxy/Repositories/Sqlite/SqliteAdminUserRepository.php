<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Sqlite;

use Flowaxy\Repositories\Contracts\AdminUserRepositoryInterface;

final class SqliteAdminUserRepository implements AdminUserRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function loadCredentials(): array
    {
        $row = $this->connection->pdo()->query('SELECT username, password_hash FROM admin_users WHERE id = 1')->fetch();

        if ($row === false) {
            return ['username' => '', 'password_hash' => ''];
        }

        return [
            'username' => (string) $row['username'],
            'password_hash' => (string) $row['password_hash'],
        ];
    }

    public function saveCredentials(string $username, string $passwordHash): bool
    {
        if ($username === '' || $passwordHash === '') {
            return false;
        }

        try {
            $stmt = $this->connection->pdo()->prepare(<<<'SQL'
                INSERT INTO admin_users (id, username, password_hash)
                VALUES (1, :username, :password_hash)
                ON CONFLICT(id) DO UPDATE SET
                    username = excluded.username,
                    password_hash = excluded.password_hash
                SQL);

            $stmt->execute([
                'username' => $username,
                'password_hash' => $passwordHash,
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function isConfigured(): bool
    {
        $credentials = $this->loadCredentials();

        return $credentials['username'] !== '' && $credentials['password_hash'] !== '';
    }
}

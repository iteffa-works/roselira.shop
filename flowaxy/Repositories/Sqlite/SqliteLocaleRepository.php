<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Sqlite;

use Flowaxy\Repositories\Contracts\LocaleRepositoryInterface;

final class SqliteLocaleRepository implements LocaleRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function loadStrings(string $locale): array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT key, value FROM locale_strings WHERE locale = :locale ORDER BY key');
        $stmt->execute(['locale' => $locale]);

        $strings = [];
        foreach ($stmt->fetchAll() as $row) {
            $strings[(string) $row['key']] = (string) $row['value'];
        }

        return $strings;
    }

    public function saveStrings(string $locale, array $translations): bool
    {
        try {
            Connection::persistLocaleStrings($this->connection->pdo(), $locale, $translations);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

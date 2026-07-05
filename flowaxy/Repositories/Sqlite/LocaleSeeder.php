<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Sqlite;

use Flowaxy\Support\JsonCodec;
use Flowaxy\Support\LocaleDefaults;

final class LocaleSeeder
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function ensure(): void
    {
        $pdo = $this->connection->pdo();
        $count = (int) $pdo->query('SELECT COUNT(*) FROM locale_strings')->fetchColumn();

        if ($count === 0) {
            foreach (LocaleDefaults::all() as $locale => $strings) {
                Connection::persistLocaleStrings($pdo, $locale, $strings);
            }

            $this->setVersion($pdo, LocaleDefaults::STRINGS_VERSION);

            return;
        }

        $version = $this->currentVersion($pdo);
        if ($version >= LocaleDefaults::STRINGS_VERSION) {
            return;
        }

        $this->syncMissing($pdo);
        $this->applyPatches($pdo);
        $this->setVersion($pdo, LocaleDefaults::STRINGS_VERSION);
    }

    private function syncMissing(\PDO $pdo): void
    {
        $stmt = $pdo->prepare(<<<'SQL'
            INSERT OR IGNORE INTO locale_strings (locale, key, value)
            VALUES (:locale, :key, :value)
            SQL);

        foreach (LocaleDefaults::all() as $locale => $strings) {
            foreach ($strings as $key => $value) {
                $stmt->execute([
                    'locale' => $locale,
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        }
    }

    private function applyPatches(\PDO $pdo): void
    {
        $stmt = $pdo->prepare(<<<'SQL'
            INSERT INTO locale_strings (locale, key, value)
            VALUES (:locale, :key, :value)
            ON CONFLICT(locale, key) DO UPDATE SET value = excluded.value
            SQL);

        foreach (LocaleDefaults::all() as $locale => $strings) {
            foreach (LocaleDefaults::patchKeys() as $key) {
                if (!isset($strings[$key])) {
                    continue;
                }

                $stmt->execute([
                    'locale' => $locale,
                    'key' => $key,
                    'value' => $strings[$key],
                ]);
            }
        }
    }

    private function currentVersion(\PDO $pdo): int
    {
        $stmt = $pdo->prepare('SELECT value FROM meta WHERE key = :key');
        $stmt->execute(['key' => 'locale_strings_version']);
        $row = $stmt->fetch();

        if ($row === false) {
            return 0;
        }

        $value = JsonCodec::decode((string) $row['value']);

        return is_int($value) ? $value : (int) $value;
    }

    private function setVersion(\PDO $pdo, int $version): void
    {
        $stmt = $pdo->prepare(<<<'SQL'
            INSERT INTO meta (key, value) VALUES (:key, :value)
            ON CONFLICT(key) DO UPDATE SET value = excluded.value
            SQL);

        $stmt->execute([
            'key' => 'locale_strings_version',
            'value' => JsonCodec::encode($version),
        ]);
    }
}

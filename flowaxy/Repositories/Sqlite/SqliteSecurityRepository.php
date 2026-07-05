<?php

declare(strict_types=1);

namespace Flowaxy\Repositories\Sqlite;

use Flowaxy\Repositories\Contracts\SecurityRepositoryInterface;
use Flowaxy\Support\JsonCodec;

final class SqliteSecurityRepository implements SecurityRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function logEvent(
        string $eventType,
        string $verdict,
        string $ip,
        string $userAgent,
        string $path,
        string $method,
        array $meta = [],
    ): void {
        $stmt = $this->connection->pdo()->prepare(<<<'SQL'
            INSERT INTO security_events (created_at, event_type, verdict, ip, user_agent, path, method, meta)
            VALUES (:created_at, :event_type, :verdict, :ip, :user_agent, :path, :method, :meta)
            SQL);

        $stmt->execute([
            'created_at' => date('c'),
            'event_type' => $eventType,
            'verdict' => $verdict,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'path' => $path,
            'method' => $method,
            'meta' => JsonCodec::encode($meta),
        ]);
    }

    public function listEvents(array $filters = [], int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $where = [];
        $params = [];

        if (($filters['ip'] ?? '') !== '') {
            $where[] = 'ip LIKE :ip';
            $params['ip'] = '%' . (string) $filters['ip'] . '%';
        }

        if (($filters['event_type'] ?? '') !== '') {
            $where[] = 'event_type = :event_type';
            $params['event_type'] = (string) $filters['event_type'];
        }

        if (($filters['verdict'] ?? '') !== '') {
            $where[] = 'verdict = :verdict';
            $params['verdict'] = (string) $filters['verdict'];
        }

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(user_agent LIKE :q OR path LIKE :q OR meta LIKE :q)';
            $params['q'] = '%' . (string) $filters['q'] . '%';
        }

        $sql = 'SELECT id, created_at, event_type, verdict, ip, user_agent, path, method, meta FROM security_events';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY id DESC LIMIT ' . $limit;

        $stmt = $this->connection->pdo()->prepare($sql);
        $stmt->execute($params);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $meta = JsonCodec::decode((string) ($row['meta'] ?? '{}'));
            $rows[] = [
                'id' => (int) $row['id'],
                'created_at' => (string) $row['created_at'],
                'event_type' => (string) $row['event_type'],
                'verdict' => (string) $row['verdict'],
                'ip' => (string) $row['ip'],
                'user_agent' => (string) $row['user_agent'],
                'path' => (string) $row['path'],
                'method' => (string) $row['method'],
                'meta' => is_array($meta) ? $meta : [],
            ];
        }

        return $rows;
    }

    public function stats(): array
    {
        $pdo = $this->connection->pdo();
        $since = date('c', time() - 86400);

        $total = (int) $pdo->query('SELECT COUNT(*) FROM security_events')->fetchColumn();
        $fraud = (int) $pdo->query("SELECT COUNT(*) FROM security_events WHERE verdict = 'fraud'")->fetchColumn();
        $suspect = (int) $pdo->query("SELECT COUNT(*) FROM security_events WHERE verdict = 'suspect'")->fetchColumn();
        $ok = (int) $pdo->query("SELECT COUNT(*) FROM security_events WHERE verdict = 'ok'")->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM security_events WHERE event_type = 'order_rate_limited' AND created_at >= :since");
        $stmt->execute(['since' => $since]);
        $rateLimited = (int) $stmt->fetchColumn();

        return [
            'total' => $total,
            'fraud' => $fraud,
            'suspect' => $suspect,
            'ok' => $ok,
            'rate_limited' => $rateLimited,
        ];
    }

    public function deleteEventsOlderThan(int $days): int
    {
        $days = max(1, $days);
        $before = date('c', time() - ($days * 86400));
        $stmt = $this->connection->pdo()->prepare('DELETE FROM security_events WHERE created_at < :before');
        $stmt->execute(['before' => $before]);

        return $stmt->rowCount();
    }

    public function deleteAllEvents(): int
    {
        $count = (int) $this->connection->pdo()->query('SELECT COUNT(*) FROM security_events')->fetchColumn();
        $this->connection->pdo()->exec('DELETE FROM security_events');

        return $count;
    }

    public function recordRateHit(string $scope, string $ip): void
    {
        $stmt = $this->connection->pdo()->prepare(<<<'SQL'
            INSERT INTO rate_limit_hits (scope, ip, created_at)
            VALUES (:scope, :ip, :created_at)
            SQL);

        $stmt->execute([
            'scope' => $scope,
            'ip' => $ip,
            'created_at' => date('c'),
        ]);
    }

    public function countRecentRateHits(string $scope, string $ip, int $windowSeconds): int
    {
        $since = date('c', time() - max(1, $windowSeconds));
        $stmt = $this->connection->pdo()->prepare(<<<'SQL'
            SELECT COUNT(*) FROM rate_limit_hits
            WHERE scope = :scope AND ip = :ip AND created_at >= :since
            SQL);
        $stmt->execute([
            'scope' => $scope,
            'ip' => $ip,
            'since' => $since,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function clearRateLimit(string $scope, ?string $ip = null): int
    {
        if ($ip === null || $ip === '') {
            $stmt = $this->connection->pdo()->prepare('DELETE FROM rate_limit_hits WHERE scope = :scope');
            $stmt->execute(['scope' => $scope]);
        } else {
            $stmt = $this->connection->pdo()->prepare('DELETE FROM rate_limit_hits WHERE scope = :scope AND ip = :ip');
            $stmt->execute(['scope' => $scope, 'ip' => $ip]);
        }

        return $stmt->rowCount();
    }

    public function pruneRateHits(int $windowSeconds): void
    {
        $before = date('c', time() - max(1, $windowSeconds));
        $stmt = $this->connection->pdo()->prepare('DELETE FROM rate_limit_hits WHERE created_at < :before');
        $stmt->execute(['before' => $before]);
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use RuntimeException;

final class PlatformSchemaBootstrap
{
    public function __construct(
        private readonly string $schemaSqlPath,
    ) {
    }

    public function apply(Connection $connection): void
    {
        $sql = file_get_contents($this->schemaSqlPath);
        if (false === $sql) {
            throw new RuntimeException('Cannot read platform schema SQL.');
        }
        foreach ($this->splitStatements($sql) as $statement) {
            $connection->executeStatement($statement);
        }
        $connection->executeStatement(
            'INSERT IGNORE INTO platform_install_state (singleton_id, status, current_step, updated_at) VALUES (1, :status, NULL, :now)',
            ['status' => 'pending', 'now' => new DateTimeImmutable()->format('Y-m-d H:i:s.u')],
        );
        $connection->executeStatement(
            'INSERT IGNORE INTO platform_maintenance (singleton_id, enabled, reason, updated_at) VALUES (1, 0, NULL, :now)',
            ['now' => new DateTimeImmutable()->format('Y-m-d H:i:s.u')],
        );
    }

    /**
     * @return list<string>
     */
    private function splitStatements(string $sql): array
    {
        $parts = preg_split('/;\s*\n/', trim($sql)) ?: [];

        return array_values(array_filter(array_map(trim(...), $parts)));
    }
}

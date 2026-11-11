<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use RuntimeException;

final class PlatformSchemaBootstrap
{
    /**
     * @param list<string> $schemaSqlPaths
     */
    public function __construct(
        private readonly array $schemaSqlPaths,
        private readonly PlatformStage5SchemaUpgrade $stage5Upgrade,
        private readonly PlatformStage6SchemaUpgrade $stage6Upgrade,
        private readonly PlatformStage6PageTreeSchemaUpgrade $stage6PageTreeUpgrade,
    ) {
    }

    public function apply(Connection $connection): void
    {
        foreach ($this->schemaSqlPaths as $schemaSqlPath) {
            $sql = file_get_contents($schemaSqlPath);
            if (false === $sql) {
                throw new RuntimeException('Cannot read platform schema SQL.');
            }
            foreach ($this->splitStatements($sql) as $statement) {
                $connection->executeStatement($statement);
            }
        }
        $connection->executeStatement(
            'INSERT IGNORE INTO platform_install_state (singleton_id, status, current_step, updated_at) VALUES (1, :status, NULL, :now)',
            ['status' => 'pending', 'now' => new DateTimeImmutable()->format('Y-m-d H:i:s.u')],
        );
        $connection->executeStatement(
            'INSERT IGNORE INTO platform_maintenance (singleton_id, enabled, reason, updated_at) VALUES (1, 0, NULL, :now)',
            ['now' => new DateTimeImmutable()->format('Y-m-d H:i:s.u')],
        );
        $this->stage5Upgrade->apply($connection);
        $this->stage6Upgrade->apply($connection);
        $this->stage6PageTreeUpgrade->apply($connection);
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

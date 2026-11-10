<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;

/**
 * Idempotent schema deltas for stage 6 (single default site constraint).
 */
final class PlatformStage6SchemaUpgrade
{
    public function apply(Connection $connection): void
    {
        if (!$this->tableExists($connection, 'platform_site')) {
            return;
        }
        if ($this->columnExists($connection, 'platform_site', 'default_mark')) {
            return;
        }
        $connection->executeStatement(
            'ALTER TABLE platform_site
             ADD COLUMN default_mark TINYINT UNSIGNED AS (IF(is_default = 1, 1, NULL)) STORED AFTER is_default,
             ADD UNIQUE KEY uq_platform_site_one_default (default_mark)',
        );
    }

    private function tableExists(Connection $connection, string $table): bool
    {
        $result = $connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table',
            ['table' => $table],
        );

        return (int) $result > 0;
    }

    private function columnExists(Connection $connection, string $table, string $column): bool
    {
        $result = $connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column',
            ['table' => $table, 'column' => $column],
        );

        return (int) $result > 0;
    }
}

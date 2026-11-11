<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;

final class PlatformStage6PageTreeSchemaUpgrade
{
    public function apply(Connection $connection): void
    {
        if ($this->tableExists($connection, 'site_page')) {
            return;
        }
        $path = dirname(__DIR__, 4).'/resources/schema/stage6_page_tree.sql';
        $sql = file_get_contents($path);
        if (false === $sql) {
            return;
        }
        foreach (preg_split('/;\s*\n/', trim($sql)) ?: [] as $statement) {
            $statement = trim($statement);
            if ('' !== $statement) {
                $connection->executeStatement($statement);
            }
        }
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
}

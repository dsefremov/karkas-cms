<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Karkas\Application\Search\SearchIndexService;

final class PlatformStage7SchemaUpgrade
{
    public function __construct(
        private readonly SearchIndexService $searchIndexes,
    ) {
    }

    public function apply(Connection $connection): void
    {
        $this->searchIndexes->ensureFulltextIndexes($connection);
        $this->ensureBlogFacadeColumns($connection);
    }

    private function ensureBlogFacadeColumns(Connection $connection): void
    {
        if ($this->tableExists($connection, 'module_mount') && !$this->columnExists($connection, 'module_mount', 'bound_page_id')) {
            $connection->executeStatement(
                'ALTER TABLE module_mount ADD COLUMN bound_page_id CHAR(36) NULL AFTER path_prefix',
            );
        }
        if ($this->tableExists($connection, 'module_instance') && !$this->columnExists($connection, 'module_instance', 'last_facade_page_id')) {
            $connection->executeStatement(
                'ALTER TABLE module_instance ADD COLUMN last_facade_page_id CHAR(36) NULL AFTER package_id',
            );
        }
    }

    private function tableExists(Connection $connection, string $table): bool
    {
        $count = $connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table',
            ['table' => $table],
        );

        return (int) $count > 0;
    }

    private function columnExists(Connection $connection, string $table, string $column): bool
    {
        $count = $connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column',
            ['table' => $table, 'column' => $column],
        );

        return (int) $count > 0;
    }
}

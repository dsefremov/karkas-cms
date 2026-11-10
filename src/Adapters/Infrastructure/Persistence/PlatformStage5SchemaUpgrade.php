<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;

/**
 * Idempotent schema deltas for stage 5 (multisite + slug redirect scope).
 */
final class PlatformStage5SchemaUpgrade
{
    public function apply(Connection $connection): void
    {
        if (!$this->tableExists($connection, 'platform_site')) {
            return;
        }
        if (!$this->columnExists($connection, 'platform_site', 'site_key')) {
            $connection->executeStatement(
                'ALTER TABLE platform_site
                 MODIFY id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT',
            );
            $connection->executeStatement(
                'ALTER TABLE platform_site
                 ADD COLUMN site_key VARCHAR(64) NULL AFTER id,
                 ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0 AFTER label,
                 ADD COLUMN canonical_host VARCHAR(255) NULL AFTER is_default',
            );
            $connection->executeStatement(
                "UPDATE platform_site SET site_key = 'default', is_default = 1 WHERE id = 1",
            );
            $connection->executeStatement(
                'ALTER TABLE platform_site MODIFY site_key VARCHAR(64) NOT NULL',
            );
            $connection->executeStatement(
                'CREATE UNIQUE INDEX uq_platform_site_key ON platform_site (site_key)',
            );
            $connection->executeStatement(
                'CREATE UNIQUE INDEX uq_platform_site_host ON platform_site (canonical_host)',
            );
        }
        if ($this->tableExists($connection, 'blog_slug_redirect')
            && !$this->columnExists($connection, 'blog_slug_redirect', 'site_id')) {
            $connection->executeStatement(
                'ALTER TABLE blog_slug_redirect
                 ADD COLUMN site_id TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER post_id',
            );
            $connection->executeStatement(
                'UPDATE blog_slug_redirect r
                 INNER JOIN blog_post p ON p.id = r.post_id
                 SET r.site_id = p.site_id',
            );
            $connection->executeStatement(
                'CREATE INDEX idx_blog_redirect_site_from ON blog_slug_redirect (site_id, from_slug)',
            );
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

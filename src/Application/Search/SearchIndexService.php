<?php

declare(strict_types=1);

namespace Karkas\Application\Search;

use Doctrine\DBAL\Connection;

final class SearchIndexService
{
    public function ensureFulltextIndexes(Connection $connection): void
    {
        if ($this->tableExists($connection, 'blog_post') && !$this->indexExists($connection, 'blog_post', 'ft_blog_search')) {
            $connection->executeStatement('ALTER TABLE blog_post ADD FULLTEXT INDEX ft_blog_search (title, body_text)');
        }
        if ($this->tableExists($connection, 'site_page') && !$this->indexExists($connection, 'site_page', 'ft_page_search')) {
            $connection->executeStatement('ALTER TABLE site_page ADD FULLTEXT INDEX ft_page_search (title, body_text)');
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

    private function indexExists(Connection $connection, string $table, string $index): bool
    {
        $result = $connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index',
            ['table' => $table, 'index' => $index],
        );

        return (int) $result > 0;
    }
}

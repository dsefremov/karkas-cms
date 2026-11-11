<?php

declare(strict_types=1);

namespace Karkas\Application\Search;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;

final class SiteSearchRepository
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function search(int $siteId, string $query, int $limit, int $offset): array
    {
        $connection = $this->connections->create();
        $limit = max(1, min(30, $limit));
        $offset = max(0, $offset);
        $params = ['site' => $siteId, 'q' => $query];

        $union = <<<SQL
            SELECT * FROM (
                SELECT
                    'blog' AS kind,
                    p.id AS item_id,
                    p.title AS title,
                    p.body_text AS body_text,
                    CONCAT('blog/', p.slug) AS url_path,
                    MATCH(p.title, p.body_text) AGAINST (:q IN NATURAL LANGUAGE MODE) AS score
                FROM blog_post p
                WHERE p.site_id = :site
                    AND p.activity = 'active'
                    AND MATCH(p.title, p.body_text) AGAINST (:q IN NATURAL LANGUAGE MODE)
                UNION ALL
                SELECT
                    'page' AS kind,
                    sp.id AS item_id,
                    sp.title AS title,
                    sp.body_text AS body_text,
                    sp.full_path AS url_path,
                    MATCH(sp.title, sp.body_text) AGAINST (:q IN NATURAL LANGUAGE MODE) AS score
                FROM site_page sp
                WHERE sp.site_id = :site
                    AND sp.activity = 'active'
                    AND MATCH(sp.title, sp.body_text) AGAINST (:q IN NATURAL LANGUAGE MODE)
            ) hits
            ORDER BY score DESC, title ASC
            LIMIT {$limit} OFFSET {$offset}
            SQL;

        $items = $connection->fetchAllAssociative($union, $params);

        $countSql = <<<'SQL'
            SELECT COUNT(*) FROM (
                SELECT p.id
                FROM blog_post p
                WHERE p.site_id = :site
                    AND p.activity = 'active'
                    AND MATCH(p.title, p.body_text) AGAINST (:q IN NATURAL LANGUAGE MODE)
                UNION ALL
                SELECT sp.id
                FROM site_page sp
                WHERE sp.site_id = :site
                    AND sp.activity = 'active'
                    AND MATCH(sp.title, sp.body_text) AGAINST (:q IN NATURAL LANGUAGE MODE)
            ) c
            SQL;
        $total = (int) $connection->fetchOne($countSql, ['site' => $siteId, 'q' => $query]);

        return ['items' => $items, 'total' => $total];
    }
}

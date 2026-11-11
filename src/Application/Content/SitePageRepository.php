<?php

declare(strict_types=1);

namespace Karkas\Application\Content;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;

final class SitePageRepository
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    public function connection(): Connection
    {
        return $this->connections->create();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $pageId): ?array
    {
        $row = $this->connection()->fetchAssociative('SELECT * FROM site_page WHERE id = :id', ['id' => $pageId]);

        return false === $row ? null : $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listBySite(int $siteId): array
    {
        return $this->connection()->fetchAllAssociative(
            'SELECT id, site_id, parent_id, slug, sort_order, title, activity, full_path, updated_at
             FROM site_page WHERE site_id = :site ORDER BY sort_order ASC, id ASC',
            ['site' => $siteId],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    /**
     * @return array<string, mixed>|null
     */
    public function findBySiteSlug(int $siteId, string $parentId, string $slug): ?array
    {
        $row = $this->connection()->fetchAssociative(
            'SELECT * FROM site_page WHERE site_id = :site AND parent_id = :parent AND slug = :slug LIMIT 1',
            ['site' => $siteId, 'parent' => $parentId, 'slug' => $slug],
        );

        return false === $row ? null : $row;
    }

    public function findBySlugOnSite(int $siteId, string $slug): ?array
    {
        $row = $this->connection()->fetchAssociative(
            'SELECT * FROM site_page WHERE site_id = :site AND slug = :slug ORDER BY CHAR_LENGTH(full_path) ASC, id ASC LIMIT 1',
            ['site' => $siteId, 'slug' => $slug],
        );

        return false === $row ? null : $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByPath(int $siteId, string $fullPath): ?array
    {
        $row = $this->connection()->fetchAssociative(
            'SELECT * FROM site_page WHERE site_id = :site AND full_path = :path',
            ['site' => $siteId, 'path' => $fullPath],
        );

        return false === $row ? null : $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActiveByPath(int $siteId, string $fullPath): ?array
    {
        $row = $this->connection()->fetchAssociative(
            'SELECT * FROM site_page WHERE site_id = :site AND full_path = :path AND activity = :active',
            ['site' => $siteId, 'path' => $fullPath, 'active' => 'active'],
        );

        return false === $row ? null : $row;
    }

    public function findRedirectTarget(int $siteId, string $fromPath): ?string
    {
        $to = $this->connection()->fetchOne(
            'SELECT to_path FROM site_page_redirect
             WHERE site_id = :site AND from_path = :from_path
             ORDER BY created_at DESC LIMIT 1',
            ['site' => $siteId, 'from_path' => $fromPath],
        );
        if (false === $to || null === $to) {
            return null;
        }

        return (string) $to;
    }

    public function lockSite(int $siteId): void
    {
        $this->connection()->executeStatement(
            'SELECT id FROM site_page WHERE site_id = :site FOR UPDATE',
            ['site' => $siteId],
        );
    }

    public function insert(
        string $id,
        int $siteId,
        string $parentId,
        string $slug,
        int $sortOrder,
        string $title,
        string $bodyText,
        string $activity,
        string $fullPath,
    ): void {
        $now = new DateTime();
        $this->connection()->insert('site_page', [
            'id' => $id,
            'site_id' => $siteId,
            'parent_id' => $parentId,
            'slug' => $slug,
            'sort_order' => $sortOrder,
            'title' => $title,
            'body_text' => $bodyText,
            'activity' => $activity,
            'full_path' => $fullPath,
            'updated_at' => $now,
        ], ['updated_at' => Types::DATETIME_MUTABLE]);
    }

    public function update(
        string $id,
        string $slug,
        int $sortOrder,
        string $title,
        string $bodyText,
        string $activity,
        string $fullPath,
        string $parentId,
    ): void {
        $this->connection()->update('site_page', [
            'parent_id' => $parentId,
            'slug' => $slug,
            'sort_order' => $sortOrder,
            'title' => $title,
            'body_text' => $bodyText,
            'activity' => $activity,
            'full_path' => $fullPath,
            'updated_at' => new DateTime(),
        ], ['id' => $id], ['updated_at' => Types::DATETIME_MUTABLE]);
    }

    public function updatePathOnly(string $id, string $fullPath): void
    {
        $this->connection()->update('site_page', [
            'full_path' => $fullPath,
            'updated_at' => new DateTime(),
        ], ['id' => $id], ['updated_at' => Types::DATETIME_MUTABLE]);
    }

    public function delete(string $id): void
    {
        $this->connection()->delete('site_page', ['id' => $id]);
    }

    public function countChildren(int $siteId, string $parentId): int
    {
        return (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM site_page WHERE site_id = :site AND parent_id = :parent',
            ['site' => $siteId, 'parent' => $parentId],
        );
    }

    public function recordPathRedirect(int $siteId, string $pageId, string $fromPath, string $toPath): void
    {
        if ($fromPath === $toPath) {
            return;
        }
        $this->connection()->insert('site_page_redirect', [
            'site_id' => $siteId,
            'page_id' => $pageId,
            'from_path' => $fromPath,
            'to_path' => $toPath,
            'created_at' => new DateTime(),
        ], ['created_at' => Types::DATETIME_MUTABLE]);
    }

    public function siblingSlugExists(int $siteId, string $parentId, string $slug, ?string $exceptId): bool
    {
        $sql = 'SELECT COUNT(*) FROM site_page WHERE site_id = :site AND parent_id = :parent AND slug = :slug';
        $params = ['site' => $siteId, 'parent' => $parentId, 'slug' => $slug];
        if (null !== $exceptId) {
            $sql .= ' AND id <> :except';
            $params['except'] = $exceptId;
        }

        return (int) $this->connection()->fetchOne($sql, $params) > 0;
    }

    public function countBySite(int $siteId): int
    {
        return (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM site_page WHERE site_id = :site',
            ['site' => $siteId],
        );
    }

    public function nextSortOrder(int $siteId, string $parentId): int
    {
        $max = $this->connection()->fetchOne(
            'SELECT COALESCE(MAX(sort_order), -1) FROM site_page WHERE site_id = :site AND parent_id = :parent',
            ['site' => $siteId, 'parent' => $parentId],
        );

        return (int) $max + 1;
    }
}

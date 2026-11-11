<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTime;
use Doctrine\DBAL\Types\Types;

final class ModuleMountRepository
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    public function ensureDirectMount(string $id, int $siteId, string $instanceId, string $pathPrefix): void
    {
        $connection = $this->connections->create();
        $now = new DateTime();
        $connection->executeStatement(
            'INSERT INTO module_mount (id, site_id, module_instance_id, mount_kind, path_prefix, bound_page_id, updated_at)
             VALUES (:id, :site, :instance, :kind, :prefix, NULL, :now)
             ON DUPLICATE KEY UPDATE module_instance_id = VALUES(module_instance_id), updated_at = VALUES(updated_at)',
            [
                'id' => $id,
                'site' => $siteId,
                'instance' => $instanceId,
                'kind' => 'direct',
                'prefix' => $pathPrefix,
                'now' => $now,
            ],
            ['now' => Types::DATETIME_MUTABLE],
        );
    }

    /**
     * @return list<string> normalized paths without leading slash, e.g. blog
     */
    public function listPathPrefixesForSite(int $siteId): array
    {
        $connection = $this->connections->create();
        $rows = $connection->fetchFirstColumn(
            'SELECT path_prefix FROM module_mount WHERE site_id = :site',
            ['site' => $siteId],
        );
        $normalized = [];
        foreach ($rows as $prefix) {
            $normalized[] = trim((string) $prefix, '/');
        }

        return $normalized;
    }

    /**
     * @return array{path_prefix: string, module_instance_id: string}|null
     */
    public function resolveByPrefix(int $siteId, string $pathPrefix): ?array
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative(
            'SELECT path_prefix, module_instance_id FROM module_mount WHERE site_id = :site AND path_prefix = :prefix',
            ['site' => $siteId, 'prefix' => $pathPrefix],
        );
        if (false === $row) {
            return null;
        }

        return [
            'path_prefix' => (string) $row['path_prefix'],
            'module_instance_id' => (string) $row['module_instance_id'],
        ];
    }

    /**
     * @return array{
     *   id: string,
     *   site_id: int,
     *   module_instance_id: string,
     *   mount_kind: string,
     *   path_prefix: string,
     *   bound_page_id: string|null
     * }|null
     */
    public function findBlogMount(int $siteId): ?array
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative(
            'SELECT id, site_id, module_instance_id, mount_kind, path_prefix, bound_page_id
             FROM module_mount WHERE site_id = :site AND path_prefix = :prefix LIMIT 1',
            ['site' => $siteId, 'prefix' => '/blog'],
        );
        if (false === $row) {
            return null;
        }
        $bound = $row['bound_page_id'] ?? null;

        return [
            'id' => (string) $row['id'],
            'site_id' => (int) $row['site_id'],
            'module_instance_id' => (string) $row['module_instance_id'],
            'mount_kind' => (string) $row['mount_kind'],
            'path_prefix' => (string) $row['path_prefix'],
            'bound_page_id' => is_string($bound) && '' !== $bound ? $bound : null,
        ];
    }

    public function setBoundPage(string $mountId, string $pageId): void
    {
        $connection = $this->connections->create();
        $now = new DateTime();
        $connection->update(
            'module_mount',
            [
                'bound_page_id' => $pageId,
                'mount_kind' => 'page_facade',
                'updated_at' => $now,
            ],
            ['id' => $mountId],
            ['updated_at' => Types::DATETIME_MUTABLE],
        );
    }

    public function clearBoundPage(string $mountId): void
    {
        $connection = $this->connections->create();
        $now = new DateTime();
        $connection->update(
            'module_mount',
            [
                'bound_page_id' => null,
                'mount_kind' => 'direct',
                'updated_at' => $now,
            ],
            ['id' => $mountId],
            ['updated_at' => Types::DATETIME_MUTABLE],
        );
    }
}

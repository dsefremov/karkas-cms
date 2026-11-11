<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTime;
use Doctrine\DBAL\Types\Types;

final class ModuleInstanceRepository
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    /**
     * @return array{id: string, site_id: int, package_id: string}|null
     */
    public function findBySiteModule(int $siteId, string $packageId): ?array
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative(
            'SELECT id, site_id, package_id FROM module_instance WHERE site_id = :site AND package_id = :package',
            ['site' => $siteId, 'package' => $packageId],
        );
        if (false === $row) {
            return null;
        }

        return [
            'id' => (string) $row['id'],
            'site_id' => (int) $row['site_id'],
            'package_id' => (string) $row['package_id'],
        ];
    }

    public function create(string $id, int $siteId, string $packageId): void
    {
        $connection = $this->connections->create();
        $now = new DateTime();
        $connection->insert('module_instance', [
            'id' => $id,
            'site_id' => $siteId,
            'package_id' => $packageId,
            'last_facade_page_id' => null,
            'updated_at' => $now,
        ], ['updated_at' => Types::DATETIME_MUTABLE]);
    }

    public function getLastFacadePageId(string $instanceId): ?string
    {
        $connection = $this->connections->create();
        $value = $connection->fetchOne(
            'SELECT last_facade_page_id FROM module_instance WHERE id = :id',
            ['id' => $instanceId],
        );
        if (false === $value || null === $value || '' === $value) {
            return null;
        }

        return (string) $value;
    }

    public function setLastFacadePageId(string $instanceId, ?string $pageId): void
    {
        $connection = $this->connections->create();
        $now = new DateTime();
        $connection->update(
            'module_instance',
            ['last_facade_page_id' => $pageId, 'updated_at' => $now],
            ['id' => $instanceId],
            ['updated_at' => Types::DATETIME_MUTABLE],
        );
    }
}

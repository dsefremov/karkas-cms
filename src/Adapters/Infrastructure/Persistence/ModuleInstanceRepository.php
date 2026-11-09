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
            'updated_at' => $now,
        ], ['updated_at' => Types::DATETIME_MUTABLE]);
    }
}

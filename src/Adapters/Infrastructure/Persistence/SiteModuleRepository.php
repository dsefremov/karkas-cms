<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTime;
use Doctrine\DBAL\Types\Types;

final class SiteModuleRepository
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    /**
     * @return array{site_id: int, package_id: string, state: string, display_name: string}|null
     */
    public function find(int $siteId, string $packageId): ?array
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative(
            'SELECT site_id, package_id, state, display_name FROM site_module WHERE site_id = :site AND package_id = :package',
            ['site' => $siteId, 'package' => $packageId],
        );
        if (false === $row) {
            return null;
        }

        return [
            'site_id' => (int) $row['site_id'],
            'package_id' => (string) $row['package_id'],
            'state' => (string) $row['state'],
            'display_name' => (string) $row['display_name'],
        ];
    }

    public function upsert(int $siteId, string $packageId, string $state, string $displayName): void
    {
        $connection = $this->connections->create();
        $now = new DateTime();
        $connection->executeStatement(
            'INSERT INTO site_module (site_id, package_id, state, display_name, updated_at)
             VALUES (:site, :package, :state, :label, :now)
             ON DUPLICATE KEY UPDATE state = VALUES(state), display_name = VALUES(display_name), updated_at = VALUES(updated_at)',
            [
                'site' => $siteId,
                'package' => $packageId,
                'state' => $state,
                'label' => $displayName,
                'now' => $now,
            ],
            ['now' => Types::DATETIME_MUTABLE],
        );
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Karkas\Domain\Platform\SemVerVersion;

final class ModuleInstallationRepository
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    /**
     * @return array{package_id: string, version: string, manifest_digest: string, staging_relative_path: string, lifecycle_status: string}|null
     */
    public function find(string $packageId): ?array
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative(
            'SELECT package_id, version, manifest_digest, staging_relative_path, lifecycle_status FROM module_installation WHERE package_id = :id',
            ['id' => $packageId],
        );

        if (false === $row) {
            return null;
        }

        return [
            'package_id' => (string) $row['package_id'],
            'version' => (string) $row['version'],
            'manifest_digest' => (string) $row['manifest_digest'],
            'staging_relative_path' => (string) $row['staging_relative_path'],
            'lifecycle_status' => (string) $row['lifecycle_status'],
        ];
    }

    /**
     * @return array<string, array{package_id: string, version: string, manifest_digest: string, staging_relative_path: string, lifecycle_status: string}>
     */
    public function allByPackageId(): array
    {
        $connection = $this->connections->create();
        $rows = $connection->fetchAllAssociative('SELECT package_id, version, manifest_digest, staging_relative_path, lifecycle_status FROM module_installation');

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['package_id']] = [
                'package_id' => (string) $row['package_id'],
                'version' => (string) $row['version'],
                'manifest_digest' => (string) $row['manifest_digest'],
                'staging_relative_path' => (string) $row['staging_relative_path'],
                'lifecycle_status' => (string) $row['lifecycle_status'],
            ];
        }

        return $map;
    }

    public function upsertInstalled(
        string $packageId,
        SemVerVersion $version,
        string $manifestDigest,
        string $stagingRelativePath,
        string $lifecycleStatus = 'installed_disabled',
    ): void {
        $connection = $this->connections->create();
        $now = new DateTime();
        $existing = $this->find($packageId);
        if (null === $existing) {
            $connection->insert('module_installation', [
                'package_id' => $packageId,
                'version' => $version->canonical,
                'manifest_digest' => $manifestDigest,
                'staging_relative_path' => $stagingRelativePath,
                'lifecycle_status' => $lifecycleStatus,
                'installed_at' => $now,
                'updated_at' => $now,
            ], [
                'installed_at' => Types::DATETIME_MUTABLE,
                'updated_at' => Types::DATETIME_MUTABLE,
            ]);

            return;
        }
        $connection->update('module_installation', [
            'version' => $version->canonical,
            'manifest_digest' => $manifestDigest,
            'staging_relative_path' => $stagingRelativePath,
            'lifecycle_status' => $lifecycleStatus,
            'updated_at' => $now,
        ], ['package_id' => $packageId], [
            'updated_at' => Types::DATETIME_MUTABLE,
        ]);
    }
}

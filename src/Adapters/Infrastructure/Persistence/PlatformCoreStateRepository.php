<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Karkas\Domain\Platform\SemVerVersion;

final class PlatformCoreStateRepository
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    /**
     * @return array{version: string, manifest_digest: string, staging_relative_path: string}|null
     */
    public function get(): ?array
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative('SELECT version, manifest_digest, staging_relative_path FROM platform_core_state WHERE singleton_id = 1');

        if (false === $row) {
            return null;
        }

        return [
            'version' => (string) $row['version'],
            'manifest_digest' => (string) $row['manifest_digest'],
            'staging_relative_path' => (string) $row['staging_relative_path'],
        ];
    }

    public function upsert(SemVerVersion $version, string $manifestDigest, string $stagingRelativePath): void
    {
        $connection = $this->connections->create();
        $now = new DateTime();
        $existing = $this->get();
        if (null === $existing) {
            $connection->insert('platform_core_state', [
                'singleton_id' => 1,
                'version' => $version->canonical,
                'manifest_digest' => $manifestDigest,
                'staging_relative_path' => $stagingRelativePath,
                'updated_at' => $now,
            ], ['updated_at' => Types::DATETIME_MUTABLE]);

            return;
        }
        $connection->update('platform_core_state', [
            'version' => $version->canonical,
            'manifest_digest' => $manifestDigest,
            'staging_relative_path' => $stagingRelativePath,
            'updated_at' => $now,
        ], ['singleton_id' => 1], ['updated_at' => Types::DATETIME_MUTABLE]);
    }
}

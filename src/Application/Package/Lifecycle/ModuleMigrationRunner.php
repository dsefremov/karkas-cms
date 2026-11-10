<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Lifecycle;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Karkas\Adapters\Infrastructure\Persistence\AppliedModuleMigrationRepository;
use Karkas\Domain\Package\Manifest\PackageManifest;
use RuntimeException;

final class ModuleMigrationRunner
{
    public function __construct(
        private readonly AppliedModuleMigrationRepository $applied,
    ) {
    }

    public function applyPending(Connection $connection, string $packageSourceRoot, PackageManifest $manifest): void
    {
        foreach ($manifest->migrations as $migration) {
            if ($this->applied->isApplied($manifest->componentId->value, $migration['id'])) {
                continue;
            }
            $this->applyOne($connection, $packageSourceRoot, $manifest->componentId->value, $migration);
        }
    }

    /**
     * @param array{id: string, checksum: string} $migration
     */
    private function applyOne(Connection $connection, string $packageRoot, string $packageId, array $migration): void
    {
        $path = rtrim($packageRoot, '/').'/migrations/'.$migration['id'].'.sql';
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Migration file missing for "%s".', $migration['id']));
        }
        $sql = file_get_contents($path);
        if (false === $sql) {
            throw new RuntimeException('Cannot read migration SQL.');
        }
        $checksum = hash('sha256', $sql);
        if ($checksum !== $migration['checksum']) {
            throw new RuntimeException(sprintf('Migration checksum mismatch for "%s".', $migration['id']));
        }
        foreach ($this->splitStatements($sql) as $statement) {
            $connection->executeStatement($statement);
        }
        $now = new DateTime();
        $connection->insert('applied_module_migration', [
            'package_id' => $packageId,
            'migration_id' => $migration['id'],
            'checksum' => $checksum,
            'applied_at' => $now,
        ], ['applied_at' => Types::DATETIME_MUTABLE]);
    }

    /**
     * @return list<string>
     */
    private function splitStatements(string $sql): array
    {
        $parts = preg_split('/;\s*\n/', trim($sql)) ?: [];

        return array_values(array_filter(array_map(trim(...), $parts)));
    }
}

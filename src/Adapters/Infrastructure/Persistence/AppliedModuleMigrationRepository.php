<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

final class AppliedModuleMigrationRepository
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    public function isApplied(string $packageId, string $migrationId): bool
    {
        $connection = $this->connections->create();
        $found = $connection->fetchOne(
            'SELECT 1 FROM applied_module_migration WHERE package_id = :package AND migration_id = :migration LIMIT 1',
            ['package' => $packageId, 'migration' => $migrationId],
        );

        return false !== $found;
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Tests\Support;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;

trait PlatformSchemaTestTrait
{
    private function bootstrapPlatformSchema(DatabaseConnectionFactory $factory, PlatformSchemaBootstrap $bootstrap): void
    {
        if (!$factory->isConfigured()) {
            self::markTestSkipped('DATABASE_URL is not configured.');
        }
        $connection = $factory->create();
        foreach ([
            'deployment_rollback_slot',
            'module_installation',
            'platform_core_state',
            'platform_operation',
            'platform_maintenance',
            'platform_install_state',
        ] as $table) {
            $connection->executeStatement('DROP TABLE IF EXISTS '.$table);
        }
        $bootstrap->apply($connection);
    }
}

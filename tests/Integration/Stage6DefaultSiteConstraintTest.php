<?php

declare(strict_types=1);

namespace Karkas\Tests\Integration;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSiteRepository;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class Stage6DefaultSiteConstraintTest extends KernelTestCase
{
    use PlatformSchemaTestTrait;

    public function testDatabaseRejectsTwoDefaultSites(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var DatabaseConnectionFactory $factory */
        $factory = $container->get(DatabaseConnectionFactory::class);
        /** @var PlatformSchemaBootstrap $bootstrap */
        $bootstrap = $container->get(PlatformSchemaBootstrap::class);
        $this->bootstrapPlatformSchema($factory, $bootstrap);

        /** @var PlatformSiteRepository $sites */
        $sites = $container->get(PlatformSiteRepository::class);
        $sites->create('alpha', 'Alpha', null, true);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('default_site_exists');
        $sites->create('beta', 'Beta', null, true);
    }
}

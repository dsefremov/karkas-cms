<?php

declare(strict_types=1);

namespace Karkas\Tests\Integration;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformCoreStateRepository;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class Stage4CoreUpgradeTest extends KernelTestCase
{
    use PlatformSchemaTestTrait;

    public function testExplicitCoreUpgradeFromCatalogPlan(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var DatabaseConnectionFactory $factory */
        $factory = $container->get(DatabaseConnectionFactory::class);
        /** @var PlatformSchemaBootstrap $bootstrap */
        $bootstrap = $container->get(PlatformSchemaBootstrap::class);
        $this->bootstrapPlatformSchema($factory, $bootstrap);

        /** @var PlatformPlanService $planService */
        $planService = $container->get(PlatformPlanService::class);
        /** @var AtomicInstallOrchestrator $installer */
        $installer = $container->get(AtomicInstallOrchestrator::class);
        $baseline = $planService->buildPlanFromCatalog([], PHP_VERSION, '8.0');
        $installer->installFromCatalog($baseline, ['karkas/core'], 'test', ['karkas/core' => '1.0.0']);

        /** @var PlatformCoreStateRepository $core */
        $core = $container->get(PlatformCoreStateRepository::class);
        self::assertSame('1.0.0', $core->get()['version']);

        $upgradePlan = $planService->buildExplicitCoreUpgradePlan([], PHP_VERSION, '8.0');
        self::assertTrue($upgradePlan->compatibility->compatible);
        $installer->installFromCatalog($upgradePlan, ['karkas/core'], 'test');

        self::assertSame('1.1.0', $core->get()['version']);
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Tests\Integration;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\ModuleInstallationRepository;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Application\Package\Compatibility\SemVerConstraintEvaluator;
use Karkas\Application\Package\Compatibility\VersionTransitionPolicy;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class Stage2InstallFlowTest extends KernelTestCase
{
    use PlatformSchemaTestTrait;

    public function testAtomicInstallRegistersBlogModule(): void
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
        $installer->installFromCatalog(
            $planService->buildPlanFromCatalog(['karkas/core'], \PHP_VERSION, '8.0'),
            ['karkas/core'],
            'test',
        );

        $plan = $planService->buildPlanFromCatalog(['karkas/blog'], \PHP_VERSION, '8.0');
        self::assertTrue($plan->compatibility->compatible);
        $installer->installFromCatalog($plan, ['karkas/blog'], 'test');

        /** @var ModuleInstallationRepository $modules */
        $modules = $container->get(ModuleInstallationRepository::class);
        $row = $modules->find('karkas/blog');
        self::assertNotNull($row);
        self::assertSame('1.0.0', $row['version']);
    }

    public function testRefusesDowngrade(): void
    {
        self::bootKernel();
        $policy = new VersionTransitionPolicy(new SemVerConstraintEvaluator());
        $this->expectException(\Karkas\Application\Package\Compatibility\DowngradeRefusalException::class);
        $policy->assertUpgradeOrEqual(
            'karkas/blog',
            new \Karkas\Domain\Platform\SemVerVersion('1.0.0'),
            new \Karkas\Domain\Platform\SemVerVersion('0.9.0'),
        );
    }
}

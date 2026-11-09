<?php

declare(strict_types=1);

namespace Karkas\Tests\Integration;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Adapters\Infrastructure\Persistence\SiteModuleRepository;
use Karkas\Application\Blog\BlogPostSaveService;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Karkas\Application\Package\Lifecycle\SiteModuleEnableService;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class Stage3BlogEnableFlowTest extends KernelTestCase
{
    use PlatformSchemaTestTrait;

    public function testEnableBlogRunsMigrationsAndAcceptsSave(): void
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
        $corePlan = $planService->buildPlanFromCatalog([], PHP_VERSION, '8.0');
        $installer->installFromCatalog($corePlan, ['karkas/core'], 'test', ['karkas/core' => '1.0.0']);
        $plan = $planService->buildPlanFromCatalog(['karkas/blog'], PHP_VERSION, '8.0');
        self::assertTrue($plan->compatibility->compatible);
        $installer->installFromCatalog($plan, ['karkas/blog'], 'test');

        /** @var SiteModuleEnableService $enable */
        $enable = $container->get(SiteModuleEnableService::class);
        $enable->enableBlogOnDefaultSite('test');

        /** @var SiteModuleRepository $siteModules */
        $siteModules = $container->get(SiteModuleRepository::class);
        $row = $siteModules->find(1, 'karkas/blog');
        self::assertNotNull($row);
        self::assertSame('enabled', $row['state']);

        /** @var BlogPostSaveService $posts */
        $posts = $container->get(BlogPostSaveService::class);
        $created = $posts->createDraft([
            'title' => 'Hello',
            'slug' => 'hello',
            'body_text' => 'Body',
            'activity' => 'inactive',
        ]);
        $saved = $posts->save((string) $created['id'], [
            'title' => 'Hello',
            'slug' => 'hello',
            'body_text' => 'Body',
            'activity' => 'active',
        ]);
        self::assertFalse($saved['noop']);
    }
}

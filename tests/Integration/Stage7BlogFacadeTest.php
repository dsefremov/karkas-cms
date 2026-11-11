<?php

declare(strict_types=1);

namespace Karkas\Tests\Integration;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Application\Blog\BlogFacadeService;
use Karkas\Application\Content\SitePageService;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Karkas\Application\Package\Lifecycle\SiteModuleEnableService;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class Stage7BlogFacadeTest extends KernelTestCase
{
    use PlatformSchemaTestTrait;

    public function testFacadeStatesRouting(): void
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
        $installer->installFromCatalog($plan, ['karkas/blog'], 'test');
        /** @var SiteModuleEnableService $enable */
        $enable = $container->get(SiteModuleEnableService::class);
        $enable->enableBlogOnDefaultSite('test');

        /** @var SitePageService $pages */
        $pages = $container->get(SitePageService::class);
        $journal = $pages->create(1, [
            'title' => 'Журнал',
            'slug' => 'journal',
            'activity' => 'active',
            'body_text' => 'Список публикаций',
        ]);
        $child = $pages->create(1, [
            'title' => 'Рубрика',
            'slug' => 'rubrics',
            'parent_id' => $journal['id'],
            'activity' => 'active',
            'body_text' => 'Рубрики',
        ]);
        self::assertSame('journal/rubrics', $child['full_path']);

        /** @var BlogFacadeService $facade */
        $facade = $container->get(BlogFacadeService::class);

        $direct = $facade->resolveBlogListRequest(1);
        self::assertSame('render_list', $direct['action']);

        $facade->bindPage(1, $journal['id']);
        self::assertTrue($facade->isBoundFacadePage(1, $journal['id']));

        $other = $pages->create(1, [
            'title' => 'News',
            'slug' => 'news',
            'activity' => 'active',
        ]);

        $facade->unbindPage(1);
        $afterUnbind = $facade->resolveBlogListRequest(1);
        self::assertSame('redirect', $afterUnbind['action']);
        $desc = $facade->describeForSite(1);
        self::assertNotNull($desc);
        self::assertSame('journal', $desc['facade_tree_path']);

        $facade->bindPage(1, $other['id']);
        $rebound = $facade->resolveBlogListRequest(1);
        self::assertSame('render_list', $rebound['action']);
        self::assertFalse($facade->isBoundFacadePage(1, $journal['id']));
        self::assertTrue($facade->isBoundFacadePage(1, $other['id']));
    }
}

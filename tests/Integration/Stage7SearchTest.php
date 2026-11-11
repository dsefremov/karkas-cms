<?php

declare(strict_types=1);

namespace Karkas\Tests\Integration;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\ModuleInstanceRepository;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Application\Blog\BlogPostRepository;
use Karkas\Application\Content\SitePageService;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Karkas\Application\Package\Lifecycle\SiteModuleEnableService;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Karkas\Application\Search\SiteSearchService;
use Karkas\Domain\Content\SitePageTree;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class Stage7SearchTest extends KernelTestCase
{
    use PlatformSchemaTestTrait;

    public function testSearchReturnsOnlyActiveContentAndRanksTitle(): void
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
        /** @var ModuleInstanceRepository $instances */
        $instances = $container->get(ModuleInstanceRepository::class);
        $module = $instances->findBySiteModule(1, 'karkas/blog');
        self::assertNotNull($module);
        $instanceId = (string) $module['id'];

        /** @var BlogPostRepository $posts */
        $posts = $container->get(BlogPostRepository::class);
        $posts->insert(
            '11111111-1111-4111-8111-111111111101',
            1,
            $instanceId,
            'hidden-karkas',
            'Скрыто про Каркас',
            'документ упоминает Каркас только в теле',
            'inactive',
            hash('sha256', 'a'),
        );
        $posts->insert(
            '11111111-1111-4111-8111-111111111102',
            1,
            $instanceId,
            'active-karkas',
            'Каркас CMS поиск',
            'Статья о платформе Каркас для блога',
            'active',
            hash('sha256', 'b'),
        );

        /** @var SitePageService $pages */
        $pages = $container->get(SitePageService::class);
        $pages->create(1, [
            'title' => 'Каркас на странице',
            'slug' => 'karkas-page',
            'body_text' => 'Описание',
            'activity' => 'active',
            'parent_id' => SitePageTree::SENTINEL_PARENT_ID,
        ]);

        $search = $container->get(SiteSearchService::class);
        assert($search instanceof SiteSearchService);
        $result = $search->search(1, 'Каркас', 1, 10);
        self::assertSame('ok', $result['status']);
        self::assertSame(2, $result['total']);
        self::assertGreaterThanOrEqual(2, count($result['items']));
        self::assertSame('Каркас CMS поиск', $result['items'][0]['title']);
        $kinds = array_column($result['items'], 'kind');
        self::assertContains('blog', $kinds);
        self::assertContains('page', $kinds);
    }

    public function testShortAndEmptyQueryRejected(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $search = $container->get(SiteSearchService::class);
        assert($search instanceof SiteSearchService);
        self::assertSame('empty_query', $search->search(1, '', 1, 10)['status']);
        self::assertSame('query_too_short', $search->search(1, 'ми', 1, 10)['status']);
    }
}

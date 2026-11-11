<?php

declare(strict_types=1);

namespace Karkas\Tests\Integration;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Application\Content\SitePageRepository;
use Karkas\Application\Content\SitePageService;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Karkas\Application\Package\Lifecycle\SiteModuleEnableService;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Karkas\Domain\Content\SitePageException;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class Stage6PageTreeTest extends KernelTestCase
{
    use PlatformSchemaTestTrait;

    private SitePageService $pages;

    private SitePageRepository $repo;

    protected function setUp(): void
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

        $pages = $container->get(SitePageService::class);
        assert($pages instanceof SitePageService);
        $this->pages = $pages;
        $repo = $container->get(SitePageRepository::class);
        assert($repo instanceof SitePageRepository);
        $this->repo = $repo;
    }

    public function testNestedPathFromParentSlugs(): void
    {
        $about = $this->pages->create(1, [
            'title' => 'О нас',
            'slug' => 'about',
            'activity' => 'active',
        ]);
        $team = $this->pages->create(1, [
            'title' => 'Команда',
            'slug' => 'team',
            'parent_id' => (string) $about['id'],
            'activity' => 'active',
        ]);
        self::assertSame('about/team', $team['full_path']);
    }

    public function testMoveIntoDescendantRejected(): void
    {
        $parent = $this->pages->create(1, ['title' => 'P', 'slug' => 'parent']);
        $child = $this->pages->create(1, [
            'title' => 'C',
            'slug' => 'child',
            'parent_id' => (string) $parent['id'],
        ]);
        try {
            $this->pages->move((string) $parent['id'], (string) $child['id'], 0);
            self::fail('Expected cycle exception');
        } catch (SitePageException $e) {
            self::assertSame('cycle', $e->errorCode());
        }
    }

    public function testMountPrefixConflict(): void
    {
        try {
            $this->pages->create(1, ['title' => 'Blog', 'slug' => 'blog', 'activity' => 'inactive']);
            self::fail('Expected mount or reserved conflict');
        } catch (SitePageException $e) {
            self::assertContains($e->errorCode(), ['mount_conflict', 'reserved_path']);
        }
    }

    public function testDescendantPathsRedirectAfterMove(): void
    {
        $about = $this->pages->create(1, ['title' => 'About', 'slug' => 'about', 'activity' => 'active']);
        $team = $this->pages->create(1, [
            'title' => 'Team',
            'slug' => 'team',
            'parent_id' => (string) $about['id'],
            'activity' => 'active',
        ]);
        $company = $this->pages->create(1, ['title' => 'Company', 'slug' => 'company', 'activity' => 'active']);
        $this->pages->move((string) $team['id'], (string) $company['id'], 0);
        $row = $this->repo->findById((string) $team['id']);
        self::assertNotNull($row);
        self::assertSame('company/team', $row['full_path']);
        self::assertSame('company/team', $this->repo->findRedirectTarget(1, 'about/team'));
    }

    public function testMoveRollsBackOnPathConflict(): void
    {
        $a = $this->pages->create(1, ['title' => 'A', 'slug' => 'branch-a', 'activity' => 'active']);
        $b = $this->pages->create(1, ['title' => 'B', 'slug' => 'branch-b', 'activity' => 'active']);
        $child = $this->pages->create(1, [
            'title' => 'Child',
            'slug' => 'kid',
            'parent_id' => (string) $a['id'],
            'activity' => 'active',
        ]);
        $this->pages->create(1, [
            'title' => 'Block',
            'slug' => 'kid',
            'parent_id' => (string) $b['id'],
            'activity' => 'active',
        ]);
        try {
            $this->pages->move((string) $child['id'], (string) $b['id'], 0);
            self::fail('Expected slug conflict');
        } catch (SitePageException $e) {
            self::assertSame('slug_conflict', $e->errorCode());
        }
        $unchanged = $this->repo->findById((string) $child['id']);
        self::assertNotNull($unchanged);
        self::assertSame('branch-a/kid', $unchanged['full_path']);
        self::assertSame((string) $a['id'], (string) $unchanged['parent_id']);
    }
}

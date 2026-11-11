<?php

declare(strict_types=1);

namespace Karkas\Tests\Integration;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Application\Blog\BlogPostRepository;
use Karkas\Application\Blog\BlogPostSaveService;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Karkas\Application\Package\Lifecycle\SiteModuleEnableService;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class Stage6BlogNoopUpdatedAtTest extends KernelTestCase
{
    use PlatformSchemaTestTrait;

    public function testNoopSaveLeavesUpdatedAtUnchanged(): void
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

        /** @var BlogPostSaveService $posts */
        $posts = $container->get(BlogPostSaveService::class);
        /** @var BlogPostRepository $repo */
        $repo = $container->get(BlogPostRepository::class);

        $created = $posts->createDraft([
            'title' => 'Timestamp',
            'slug' => 'timestamp',
            'body_text' => 'Body',
            'activity' => 'inactive',
        ]);
        $postId = (string) $created['id'];
        $payload = [
            'title' => 'Timestamp',
            'slug' => 'timestamp',
            'body_text' => 'Body',
            'activity' => 'inactive',
        ];
        $posts->save($postId, $payload);
        $rowBefore = $repo->find($postId);
        self::assertNotNull($rowBefore);
        $before = (string) $rowBefore['updated_at'];
        usleep(50_000);
        $noop = $posts->save($postId, $payload);
        self::assertTrue($noop['noop']);
        $rowAfter = $repo->find($postId);
        self::assertNotNull($rowAfter);
        $after = (string) $rowAfter['updated_at'];
        self::assertSame($before, $after);
    }
}

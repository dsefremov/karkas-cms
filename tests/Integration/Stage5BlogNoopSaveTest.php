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

final class Stage5BlogNoopSaveTest extends KernelTestCase
{
    use PlatformSchemaTestTrait;

    public function testNoopSaveDoesNotAppendVersionOrPrune(): void
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
            'title' => 'Noop',
            'slug' => 'noop',
            'body_text' => 'Body',
            'activity' => 'inactive',
        ]);
        $postId = (string) $created['id'];
        self::assertSame(1, $repo->countVersions($postId));

        $payload = [
            'title' => 'Noop',
            'slug' => 'noop',
            'body_text' => 'Body',
            'activity' => 'inactive',
        ];
        $first = $posts->save($postId, $payload);
        self::assertTrue($first['noop']);
        self::assertSame(1, $repo->countVersions($postId));

        $second = $posts->save($postId, $payload);
        self::assertTrue($second['noop']);
        self::assertSame(1, $repo->countVersions($postId));
    }
}

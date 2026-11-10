<?php

declare(strict_types=1);

namespace Karkas\Tests\Integration;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Application\Blog\BlogPostSaveService;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Karkas\Application\Package\Lifecycle\SiteModuleEnableService;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class Stage4BlogVersionPruneTest extends KernelTestCase
{
    use PlatformSchemaTestTrait;

    public function testSavePrunesVersionHistoryToTen(): void
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
        $created = $posts->createDraft([
            'title' => 'Prune test',
            'slug' => 'prune-test',
            'body_text' => 'Body',
            'activity' => 'inactive',
        ]);
        $postId = (string) $created['id'];
        for ($i = 1; $i <= 11; ++$i) {
            $posts->save($postId, [
                'title' => 'Title '.$i,
                'slug' => 'prune-test',
                'body_text' => 'Body '.$i,
                'activity' => 'active',
            ]);
        }
        $history = $posts->listVersionHistory($postId);
        self::assertCount(BlogPostSaveService::MAX_VERSION_HISTORY, $history);
        self::assertSame('Title 11', $history[0]['title']);
        self::assertSame(12, $history[0]['version_seq']);
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Tests\Integration;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSiteRepository;
use Karkas\Application\Blog\BlogPostRepository;
use Karkas\Application\Blog\BlogPostSaveService;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Karkas\Application\Package\Lifecycle\SiteModuleEnableService;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class Stage5MultisiteSlugRedirectTest extends KernelTestCase
{
    use PlatformSchemaTestTrait;

    public function testSlugRedirectIsScopedBySiteId(): void
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

        /** @var PlatformSiteRepository $sites */
        $sites = $container->get(PlatformSiteRepository::class);
        $sites->ensureDefaultSite('Default');
        $second = $sites->create('partner', 'Партнёр', 'partner.example.test');

        /** @var SiteModuleEnableService $enable */
        $enable = $container->get(SiteModuleEnableService::class);
        $enable->enableBlogOnSite(PlatformSiteRepository::DEFAULT_SITE_ID, 'test', 'Default');
        $enable->enableBlogOnSite($second['id'], 'test', $second['label']);

        /** @var BlogPostSaveService $posts */
        $posts = $container->get(BlogPostSaveService::class);
        /** @var BlogPostRepository $repo */
        $repo = $container->get(BlogPostRepository::class);

        $onDefault = $posts->createDraft([
            'title' => 'A',
            'slug' => 'shared-old',
            'body_text' => '1',
            'activity' => 'active',
        ]);
        $posts->save((string) $onDefault['id'], [
            'title' => 'A',
            'slug' => 'shared-new-default',
            'body_text' => '1',
            'activity' => 'active',
        ]);

        $connection = $factory->create();
        $instanceRow = $connection->fetchAssociative(
            'SELECT id FROM module_instance WHERE site_id = :site AND package_id = :pkg',
            ['site' => $second['id'], 'pkg' => 'karkas/blog'],
        );
        self::assertNotFalse($instanceRow);
        $instanceId = (string) $instanceRow['id'];
        $postId = bin2hex(random_bytes(16));
        $etag = hash('sha256', json_encode([
            'title' => 'B',
            'slug' => 'shared-old',
            'body_text' => '2',
            'activity' => 'active',
        ], JSON_THROW_ON_ERROR));
        $repo->insert($postId, $second['id'], $instanceId, 'shared-old', 'B', '2', 'active', $etag);
        $repo->recordSlugRedirect($second['id'], $postId, 'shared-old', 'shared-new-partner');

        self::assertSame('shared-new-default', $repo->resolvePublishedSlug(PlatformSiteRepository::DEFAULT_SITE_ID, 'shared-old'));
        self::assertSame('shared-new-partner', $repo->resolvePublishedSlug($second['id'], 'shared-old'));
        self::assertNull($repo->resolvePublishedSlug($second['id'], 'shared-new-default'));
    }
}

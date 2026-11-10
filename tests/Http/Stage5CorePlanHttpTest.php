<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Stage5CorePlanHttpTest extends WebTestCase
{
    use PlatformSchemaTestTrait;

    private const ADMIN_PASSWORD = 'test-admin-password-stage2';

    public function testGetPlanWithIncludeCoreReturnsCoreCandidate(): void
    {
        $client = static::createClient();
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

        $this->login($client);
        $client->request('GET', '/api/v1/platform/plan?include_core=1');
        self::assertResponseIsSuccessful();
        $json = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('plan', $json);
        self::assertArrayHasKey('targets', $json['plan']);
        $packageIds = array_column($json['plan']['targets'], 'package_id');
        self::assertContains('karkas/core', $packageIds);
    }

    private function login(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): void
    {
        $client->request(
            'POST',
            '/api/v1/admin/session',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['password' => self::ADMIN_PASSWORD], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
    }
}

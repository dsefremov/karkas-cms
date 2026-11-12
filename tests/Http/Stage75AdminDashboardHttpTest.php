<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Stage75AdminDashboardHttpTest extends WebTestCase
{
    use PlatformSchemaTestTrait;

    private const ADMIN_PASSWORD = 'test-admin-password-stage2';

    public function testDashboardRequiresSession(): void
    {
        $client = static::createClient();
        $this->bootstrapSchema($client);
        $client->request('GET', '/api/v1/admin/dashboard');
        self::assertResponseStatusCodeSame(401);
    }

    public function testDashboardReturnsCountsAfterBootstrap(): void
    {
        $client = static::createClient();
        $this->bootstrapSchema($client);
        $this->login($client);
        $this->bootstrapBlog($client);

        $client->request('GET', '/api/v1/admin/dashboard');
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('posts_active', $payload);
        self::assertArrayHasKey('posts_inactive', $payload);
        self::assertArrayHasKey('pages_count', $payload);
        self::assertArrayHasKey('recent_operations', $payload);
        self::assertIsArray($payload['recent_operations']);
    }

    private function bootstrapSchema(mixed $client): void
    {
        $container = static::getContainer();
        /** @var DatabaseConnectionFactory $factory */
        $factory = $container->get(DatabaseConnectionFactory::class);
        /** @var PlatformSchemaBootstrap $bootstrap */
        $bootstrap = $container->get(PlatformSchemaBootstrap::class);
        $this->bootstrapPlatformSchema($factory, $bootstrap);
    }

    private function login(mixed $client): void
    {
        $client->request('POST', '/api/v1/admin/session', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['password' => self::ADMIN_PASSWORD], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }

    private function bootstrapBlog(mixed $client): void
    {
        $client->request('POST', '/api/v1/sites', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'site_key' => 'default',
            'label' => 'Default',
            'canonical_host' => 'localhost',
            'is_default' => true,
        ], JSON_THROW_ON_ERROR));
        self::assertTrue(in_array($client->getResponse()->getStatusCode(), [201, 409], true));

        $client->request('POST', '/api/v1/platform/install', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['modules' => ['karkas/blog']], JSON_THROW_ON_ERROR));
        self::assertTrue(in_array($client->getResponse()->getStatusCode(), [202, 409], true));

        $client->request('POST', '/api/v1/sites/default/modules/karkas/blog/enable', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['site_label' => 'Журнал'], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(202);
    }
}

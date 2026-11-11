<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Stage75BlogAdminHttpTest extends WebTestCase
{
    use PlatformSchemaTestTrait;

    private const ADMIN_PASSWORD = 'test-admin-password-stage2';

    public function testSitesListRequiresAdminSession(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var DatabaseConnectionFactory $factory */
        $factory = $container->get(DatabaseConnectionFactory::class);
        /** @var PlatformSchemaBootstrap $bootstrap */
        $bootstrap = $container->get(PlatformSchemaBootstrap::class);
        $this->bootstrapPlatformSchema($factory, $bootstrap);

        $client->request('GET', '/api/v1/sites');
        self::assertResponseStatusCodeSame(401);
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('error', $payload);
    }

    public function testDoubleEnableBlogReturnsSuccess(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var DatabaseConnectionFactory $factory */
        $factory = $container->get(DatabaseConnectionFactory::class);
        /** @var PlatformSchemaBootstrap $bootstrap */
        $bootstrap = $container->get(PlatformSchemaBootstrap::class);
        $this->bootstrapPlatformSchema($factory, $bootstrap);
        $this->login($client);
        $client->request('POST', '/api/v1/sites', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'site_key' => 'default',
            'label' => 'Default',
            'canonical_host' => 'localhost',
            'is_default' => true,
        ], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(201);
        $client->request('POST', '/api/v1/platform/install', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['modules' => ['karkas/blog']], JSON_THROW_ON_ERROR));
        self::assertTrue(in_array($client->getResponse()->getStatusCode(), [202, 409], true));

        $enableBody = json_encode(['site_label' => 'Label from test'], JSON_THROW_ON_ERROR);
        $client->request('POST', '/api/v1/sites/default/modules/karkas/blog/enable', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], $enableBody);
        self::assertResponseStatusCodeSame(202);

        $client->request('POST', '/api/v1/sites/default/modules/karkas/blog/enable', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], $enableBody);
        self::assertResponseStatusCodeSame(202);
    }

    public function testPlatformInstallUnknownModuleReturnsJsonError(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var DatabaseConnectionFactory $factory */
        $factory = $container->get(DatabaseConnectionFactory::class);
        /** @var PlatformSchemaBootstrap $bootstrap */
        $bootstrap = $container->get(PlatformSchemaBootstrap::class);
        $this->bootstrapPlatformSchema($factory, $bootstrap);
        $this->login($client);

        $client->request('POST', '/api/v1/platform/install', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['modules' => ['karkas/not-a-real-package']], JSON_THROW_ON_ERROR));
        $status = $client->getResponse()->getStatusCode();
        self::assertContains($status, [422, 500], 'install failure must return structured JSON, not uncaught 500 HTML');
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        if (422 === $status) {
            self::assertArrayHasKey('compatible', $payload);
            self::assertFalse($payload['compatible']);
        } else {
            self::assertArrayHasKey('error', $payload);
        }
    }

    private function login(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): void
    {
        $client->request('POST', '/api/v1/admin/session', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['password' => self::ADMIN_PASSWORD], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }
}

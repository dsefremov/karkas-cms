<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Stage2PlatformHttpTest extends WebTestCase
{
    use PlatformSchemaTestTrait;

    private const ADMIN_PASSWORD = 'test-admin-password-stage2';

    public function testZipUploadDisabledByDefault(): void
    {
        $client = static::createClient();
        $this->login($client);
        $client->request('GET', '/api/v1/admin/packages/zip-upload');
        self::assertResponseIsSuccessful();
        $json = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($json['enabled']);
    }

    public function testInstallBlogFromCatalog(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var DatabaseConnectionFactory $factory */
        $factory = $container->get(DatabaseConnectionFactory::class);
        /** @var PlatformSchemaBootstrap $bootstrap */
        $bootstrap = $container->get(PlatformSchemaBootstrap::class);
        $this->bootstrapPlatformSchema($factory, $bootstrap);

        $this->login($client);
        $client->request(
            'POST',
            '/api/v1/installer/bootstrap',
            [],
            [],
            [
                'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
                'CONTENT_TYPE' => 'application/json',
            ],
            '{}',
        );
        self::assertResponseIsSuccessful();

        $client->request(
            'POST',
            '/api/v1/installer/complete',
            [],
            [],
            [
                'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
                'CONTENT_TYPE' => 'application/json',
            ],
            '{}',
        );
        self::assertResponseIsSuccessful();

        $client->request(
            'POST',
            '/api/v1/platform/install',
            [],
            [],
            [
                'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['modules' => ['karkas/blog']], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(202);

        $client->request('GET', '/api/v1/platform/versions');
        $versions = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $blog = null;
        foreach ($versions['packages'] as $row) {
            if ('karkas/blog' === $row['package_id']) {
                $blog = $row;
            }
        }
        self::assertNotNull($blog);
        self::assertSame('1.0.0', $blog['current']);
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

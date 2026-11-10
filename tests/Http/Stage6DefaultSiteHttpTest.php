<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Stage6DefaultSiteHttpTest extends WebTestCase
{
    use PlatformSchemaTestTrait;

    private const ADMIN_PASSWORD = 'test-admin-password-stage2';

    public function testSecondDefaultSiteRejected(): void
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
            '/api/v1/sites',
            [],
            [],
            [
                'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'site_key' => 'primary',
                'label' => 'Основной',
                'is_default' => true,
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);

        $client->request(
            'POST',
            '/api/v1/sites',
            [],
            [],
            [
                'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'site_key' => 'secondary-default',
                'label' => 'Второй default',
                'is_default' => true,
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(409);
        $json = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('default_site_exists', $json['error']);
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

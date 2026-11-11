<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Stage7SitesHttpTest extends WebTestCase
{
    use PlatformSchemaTestTrait;

    private const ADMIN_PASSWORD = 'test-admin-password-stage2';

    public function testMakeDefaultTransfersFlag(): void
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
            ['HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['site_key' => 'default', 'label' => 'Default', 'is_default' => true], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);

        $client->request(
            'POST',
            '/api/v1/sites',
            [],
            [],
            ['HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['site_key' => 'main', 'label' => 'Main', 'canonical_host' => 'main.test'], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $siteId = (int) $created['site']['id'];

        $client->request('POST', '/api/v1/sites/'.$siteId.'/make-default', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], '{}');
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/sites');
        $list = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $main = array_values(array_filter($list['sites'], static fn (array $s): bool => 'main' === $s['site_key']))[0] ?? null;
        self::assertNotNull($main);
        self::assertTrue($main['is_default']);
    }

    public function testCannotDeleteDefaultSite(): void
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
            ['HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD, 'CONTENT_TYPE' => 'application/json'],
            json_encode([
                'site_key' => 'default',
                'label' => 'Default',
                'is_default' => true,
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);

        $client->request('DELETE', '/api/v1/sites/1', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(409);
        $json = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('default_site_delete_forbidden', $json['error']);
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

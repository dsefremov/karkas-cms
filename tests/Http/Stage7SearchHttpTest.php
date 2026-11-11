<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Stage7SearchHttpTest extends WebTestCase
{
    use PlatformSchemaTestTrait;

    public function testSearchPageRenders(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var DatabaseConnectionFactory $factory */
        $factory = $container->get(DatabaseConnectionFactory::class);
        /** @var PlatformSchemaBootstrap $bootstrap */
        $bootstrap = $container->get(PlatformSchemaBootstrap::class);
        $this->bootstrapPlatformSchema($factory, $bootstrap);
        $client->request(
            'POST',
            '/api/v1/admin/session',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['password' => 'test-admin-password-stage2'], JSON_THROW_ON_ERROR),
        );
        $client->request(
            'POST',
            '/api/v1/sites',
            [],
            [],
            [
                'HTTP_X-Karkas-Reauth' => 'test-admin-password-stage2',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'site_key' => 'default',
                'label' => 'Default',
                'canonical_host' => 'localhost',
                'is_default' => true,
            ], JSON_THROW_ON_ERROR),
        );

        $client->request('GET', '/search');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('data-testid="search-page"', (string) $client->getResponse()->getContent());
    }

    public function testLegacyFlatRedirect(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var DatabaseConnectionFactory $factory */
        $factory = $container->get(DatabaseConnectionFactory::class);
        /** @var PlatformSchemaBootstrap $bootstrap */
        $bootstrap = $container->get(PlatformSchemaBootstrap::class);
        $this->bootstrapPlatformSchema($factory, $bootstrap);

        $client->request(
            'POST',
            '/api/v1/admin/session',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['password' => 'test-admin-password-stage2'], JSON_THROW_ON_ERROR),
        );
        $client->request(
            'POST',
            '/api/v1/sites',
            [],
            [],
            [
                'HTTP_X-Karkas-Reauth' => 'test-admin-password-stage2',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'site_key' => 'default',
                'label' => 'Default',
                'canonical_host' => 'localhost',
                'is_default' => true,
            ], JSON_THROW_ON_ERROR),
        );
        $client->request(
            'POST',
            '/api/v1/sites/1/pages',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'About', 'slug' => 'about', 'activity' => 'active'], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);
        /** @var array{id: string} $about */
        $about = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $client->request(
            'POST',
            '/api/v1/sites/1/pages',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Team',
                'slug' => 'team',
                'parent_id' => $about['id'],
                'activity' => 'active',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);
        $client->request('GET', '/p/team');
        self::assertResponseStatusCodeSame(301);
        self::assertSame('/about/team', $client->getResponse()->headers->get('Location'));
    }
}

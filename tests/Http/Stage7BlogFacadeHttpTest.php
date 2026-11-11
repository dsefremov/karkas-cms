<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Stage7BlogFacadeHttpTest extends WebTestCase
{
    use PlatformSchemaTestTrait;

    private const ADMIN_PASSWORD = 'test-admin-password-stage2';

    public function testBoundAndUnboundPublicRedirects(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var DatabaseConnectionFactory $factory */
        $factory = $container->get(DatabaseConnectionFactory::class);
        /** @var PlatformSchemaBootstrap $bootstrap */
        $bootstrap = $container->get(PlatformSchemaBootstrap::class);
        $this->bootstrapPlatformSchema($factory, $bootstrap);
        $this->bootstrapSiteAndBlog($client);
        $journalId = $this->createPage($client, 'journal', 'Журнал');
        $client->request('POST', '/api/v1/sites/1/modules/karkas/blog/bind-page', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['page_id' => $journalId], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();

        $client->request('GET', '/journal');
        self::assertResponseStatusCodeSame(301);
        self::assertSame('/blog', $client->getResponse()->headers->get('Location'));

        $client->request('GET', '/blog');
        self::assertResponseIsSuccessful();

        $childId = $this->createPage($client, 'rubrics', 'Рубрика', $journalId);
        self::assertNotSame('', $childId);
        $client->request('GET', '/journal/rubrics');
        self::assertResponseIsSuccessful();

        $client->request('POST', '/api/v1/sites/1/modules/karkas/blog/unbind-page', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], '{}');
        self::assertResponseIsSuccessful();

        $client->request('GET', '/blog');
        self::assertResponseStatusCodeSame(301);
        self::assertSame('/journal', $client->getResponse()->headers->get('Location'));

        $client->request('GET', '/journal');
        self::assertResponseIsSuccessful();
    }

    private function bootstrapSiteAndBlog(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): void
    {
        $client->request('POST', '/api/v1/admin/session', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['password' => self::ADMIN_PASSWORD], JSON_THROW_ON_ERROR));
        $client->request('POST', '/api/v1/sites', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'site_key' => 'default',
            'label' => 'Default',
            'canonical_host' => 'localhost',
            'is_default' => true,
        ], JSON_THROW_ON_ERROR));
        $client->request('POST', '/api/v1/platform/install', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['modules' => ['karkas/blog']], JSON_THROW_ON_ERROR));
        $client->request('POST', '/api/v1/sites/default/modules/karkas/blog/enable', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['site_label' => 'Demo'], JSON_THROW_ON_ERROR));
    }

    private function createPage(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $slug, string $title, ?string $parentId = null): string
    {
        $payload = ['title' => $title, 'slug' => $slug, 'activity' => 'active', 'body_text' => 'body'];
        if (null !== $parentId) {
            $payload['parent_id'] = $parentId;
        }
        $client->request('POST', '/api/v1/sites/1/pages', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload, JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(201);
        /** @var array{id: string} $created */
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        return $created['id'];
    }
}

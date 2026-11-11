<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Tests\Support\PlatformSchemaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Stage75BlogPostsHttpTest extends WebTestCase
{
    use PlatformSchemaTestTrait;

    private const ADMIN_PASSWORD = 'test-admin-password-stage2';

    public function testListGetAndRestoreVersion(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var DatabaseConnectionFactory $factory */
        $factory = $container->get(DatabaseConnectionFactory::class);
        /** @var PlatformSchemaBootstrap $bootstrap */
        $bootstrap = $container->get(PlatformSchemaBootstrap::class);
        $this->bootstrapPlatformSchema($factory, $bootstrap);
        $this->login($client);
        $this->bootstrapBlog($client);

        $client->request('POST', '/api/v1/blog/posts', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'List test',
            'slug' => 'list-test-'.bin2hex(random_bytes(2)),
            'body_text' => 'v1',
            'activity' => 'inactive',
        ], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $postId = (string) $created['id'];

        $client->request('GET', '/api/v1/blog/posts');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($list['posts']);

        $client->request('POST', '/api/v1/blog/posts/'.$postId.'/save', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'List test v2',
            'slug' => $created['slug'] ?? 'list-test',
            'body_text' => 'v2',
            'activity' => 'active',
        ], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();

        $client->request('POST', '/api/v1/blog/posts/'.$postId.'/versions/1/restore');
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/blog/posts/'.$postId);
        self::assertResponseIsSuccessful();
        $get = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('List test', $get['post']['title']);
    }

    private function login(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): void
    {
        $client->request('POST', '/api/v1/admin/session', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['password' => self::ADMIN_PASSWORD], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();
    }

    private function bootstrapBlog(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): void
    {
        $client->request('POST', '/api/v1/sites', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'site_key' => 'default',
            'label' => 'Default',
            'is_default' => true,
        ], JSON_THROW_ON_ERROR));
        $client->request('POST', '/api/v1/platform/install', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['modules' => ['karkas/blog']], JSON_THROW_ON_ERROR));
        $client->request('POST', '/api/v1/sites/default/modules/karkas/blog/enable', [], [], [
            'HTTP_X-Karkas-Reauth' => self::ADMIN_PASSWORD,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['site_label' => 'Default'], JSON_THROW_ON_ERROR));
    }
}

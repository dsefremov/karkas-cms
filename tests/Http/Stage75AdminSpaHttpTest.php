<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Stage75AdminSpaHttpTest extends WebTestCase
{
    public function testAdminSpaRoutesServeIndexHtml(): void
    {
        $client = static::createClient();
        foreach (['/admin/', '/admin/dashboard', '/admin/modules', '/admin/pages'] as $path) {
            $client->request('GET', $path);
            self::assertResponseIsSuccessful(sprintf('Expected 200 for %s', $path));
            self::assertStringContainsString('id="root"', (string) $client->getResponse()->getContent());
        }
    }
}

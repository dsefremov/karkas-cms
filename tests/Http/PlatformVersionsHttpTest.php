<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PlatformVersionsHttpTest extends WebTestCase
{
    public function testVersionsEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/platform/versions');
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('karkas-platform-versions/v1', $payload['schema']);
        self::assertSame(1, $payload['stage']);
        self::assertNotEmpty($payload['packages']);
        $core = null;
        foreach ($payload['packages'] as $row) {
            if ('karkas/core' === $row['package_id']) {
                $core = $row;
                break;
            }
        }
        self::assertNotNull($core);
        self::assertSame('1.0.0', $core['current']);
        self::assertSame('1.1.0', $core['available']);
    }
}

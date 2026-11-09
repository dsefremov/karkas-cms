<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeAndHealthHttpTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testHomeReturnsOkPayload(): void
    {
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        $json = json_decode($this->client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ok', $json['status']);
        self::assertSame(3, $json['stage']);
    }

    public function testHealthEndpoint(): void
    {
        $this->client->request('GET', '/health');
        self::assertResponseIsSuccessful();
        $json = json_decode($this->client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('healthy', $json['status']);
        self::assertArrayHasKey('php', $json['checks']);
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Karkas\Adapters\Entry\Http\Demo\DemoApiGate;
use Karkas\Adapters\Entry\Http\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

final class AdminDemoRoutesEnvironmentTest extends WebTestCase
{
    public function testGateIsOpenOnlyInDevOrTestWithFlag(): void
    {
        self::assertTrue(new DemoApiGate('dev', '1')->isEnabled());
        self::assertTrue(new DemoApiGate('test', '1')->isEnabled());
        self::assertFalse(new DemoApiGate('prod', '1')->isEnabled());
        self::assertFalse(new DemoApiGate('dev', '0')->isEnabled());
        self::assertFalse(new DemoApiGate('dev', null)->isEnabled());
        self::assertFalse(new DemoApiGate('test', '')->isEnabled());
    }

    public function testDemoSeedAndResetReturnNotFoundInProd(): void
    {
        $kernel = new Kernel('prod', false);
        $kernel->boot();
        try {
            foreach (['/api/v1/admin/demo/seed', '/api/v1/admin/demo/reset'] as $path) {
                $response = $kernel->handle(Request::create($path, 'POST'));
                self::assertSame(404, $response->getStatusCode(), $path);
            }
        } finally {
            $kernel->shutdown();
        }
    }

    public function testDemoSeedAndResetRequireAuthInTestEnvWithFlag(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/admin/demo/seed');
        self::assertResponseStatusCodeSame(401);

        $client->request('POST', '/api/v1/admin/demo/reset');
        self::assertResponseStatusCodeSame(401);
    }
}

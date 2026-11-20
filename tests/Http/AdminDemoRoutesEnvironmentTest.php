<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

final class AdminDemoRoutesEnvironmentTest extends WebTestCase
{
    public function testDemoSeedAndResetRoutesAreNotRegisteredInProd(): void
    {
        static::ensureKernelShutdown();
        static::bootKernel(['environment' => 'prod', 'debug' => false]);

        $router = static::getContainer()->get(RouterInterface::class);
        $routes = $router->getRouteCollection();

        self::assertNull($routes->get('api_admin_demo_seed'));
        self::assertNull($routes->get('api_admin_demo_reset'));
    }

    public function testDemoSeedAndResetReturnNotFoundInProd(): void
    {
        $client = static::createClient(['environment' => 'prod', 'debug' => false]);

        $client->request('POST', '/api/v1/admin/demo/seed');
        self::assertResponseStatusCodeSame(404);

        $client->request('POST', '/api/v1/admin/demo/reset');
        self::assertResponseStatusCodeSame(404);
    }
}
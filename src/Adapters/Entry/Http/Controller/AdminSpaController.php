<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminSpaController
{
    #[Route(
        '/admin/{spaPath}',
        name: 'admin_spa',
        requirements: ['spaPath' => '(?!assets(?:/|$)).*'],
        defaults: ['spaPath' => ''],
        methods: ['GET'],
        priority: -64,
    )]
    public function index(string $spaPath): Response
    {
        unset($spaPath);
        $index = dirname(__DIR__, 5).'/public/admin/index.html';
        if (!is_file($index)) {
            return new Response('Admin UI build missing', 404);
        }

        return new Response((string) file_get_contents($index), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}

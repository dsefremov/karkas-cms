<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Domain\Platform\Version;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): JsonResponse
    {
        return new JsonResponse([
            'name' => Version::PRODUCT,
            'stage' => (int) Version::STAGE,
            'status' => 'ok',
            'message' => 'Stage 7 — FULLTEXT search, legacy /p redirect, default site and blog column in admin.',
        ]);
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'healthy',
            'checks' => [
                'php' => [
                    'version' => PHP_VERSION,
                    'sapi' => PHP_SAPI,
                ],
            ],
        ]);
    }
}

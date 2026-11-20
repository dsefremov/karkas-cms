<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Admin\AdminDemoResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class AdminDemoResetController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly AdminDemoResetService $demoReset,
    ) {
    }

    #[Route('/api/v1/admin/demo/reset', name: 'api_admin_demo_reset', methods: ['POST'])]
    public function reset(): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        try {
            return new JsonResponse($this->demoReset->resetAndSeed());
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }
    }
}
<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Admin\AdminDashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class AdminDashboardController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly AdminDashboardService $dashboard,
    ) {
    }

    #[Route('/api/v1/admin/dashboard', name: 'api_admin_dashboard', methods: ['GET'])]
    public function summary(): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        try {
            return new JsonResponse($this->dashboard->summary());
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }
    }
}

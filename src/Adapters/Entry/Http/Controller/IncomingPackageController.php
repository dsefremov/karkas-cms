<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Package\Quarantine\IncomingPackageScanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class IncomingPackageController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly IncomingPackageScanService $scanService,
    ) {
    }

    #[Route('/api/v1/admin/packages/scan-incoming', name: 'api_packages_scan_incoming', methods: ['POST'])]
    public function scan(Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        return new JsonResponse($this->scanService->scanAndIngest());
    }
}

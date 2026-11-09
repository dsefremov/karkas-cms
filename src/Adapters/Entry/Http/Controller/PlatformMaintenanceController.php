<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Adapters\Infrastructure\Persistence\MaintenanceModeRepository;
use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PlatformMaintenanceController extends AbstractController
{
    public function __construct(
        private readonly MaintenanceModeRepository $maintenance,
        private readonly AdminAuthService $adminAuth,
    ) {
    }

    #[Route('/api/v1/admin/platform/maintenance', name: 'api_platform_maintenance_get', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $this->adminAuth->assertAuthenticated();

        return new JsonResponse(['enabled' => $this->maintenance->isEnabled()]);
    }

    #[Route('/api/v1/admin/platform/maintenance', name: 'api_platform_maintenance_post', methods: ['POST'])]
    public function toggle(Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        $body = json_decode($request->getContent(), true);
        $enabled = is_array($body) && ($body['enabled'] ?? false) === true;
        $reason = is_array($body) && isset($body['reason']) && is_string($body['reason']) ? $body['reason'] : null;
        $this->maintenance->setEnabled($enabled, $reason);

        return new JsonResponse(['enabled' => $enabled]);
    }
}

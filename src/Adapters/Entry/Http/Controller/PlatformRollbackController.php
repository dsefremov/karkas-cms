<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Adapters\Infrastructure\Persistence\RollbackSlotRepository;
use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class PlatformRollbackController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly AtomicInstallOrchestrator $installer,
        private readonly RollbackSlotRepository $rollbackSlot,
    ) {
    }

    #[Route('/api/v1/admin/platform/rollback', name: 'api_platform_rollback_get', methods: ['GET'])]
    public function slot(): JsonResponse
    {
        $this->adminAuth->assertAuthenticated();
        $slot = $this->rollbackSlot->getSlot();
        if (null === $slot) {
            return new JsonResponse(['available' => false]);
        }

        return new JsonResponse([
            'available' => true,
            'generation_id' => $slot->generationId,
            'core_version' => $slot->coreVersion->canonical,
            'module_versions' => array_map(static fn ($v) => $v->canonical, $slot->moduleVersions),
        ]);
    }

    #[Route('/api/v1/admin/platform/rollback', name: 'api_platform_rollback_post', methods: ['POST'])]
    public function execute(Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        try {
            $this->installer->executeRollback('admin');
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse(['status' => 'succeeded']);
    }
}

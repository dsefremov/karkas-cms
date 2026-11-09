<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\InstallStateRepository;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSchemaBootstrap;
use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Package\Catalog\PackageCatalogRepository;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class InstallerController extends AbstractController
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
        private readonly PlatformSchemaBootstrap $schemaBootstrap,
        private readonly InstallStateRepository $installState,
        private readonly AdminAuthService $adminAuth,
        private readonly PlatformPlanService $planService,
        private readonly AtomicInstallOrchestrator $orchestrator,
        private readonly PackageCatalogRepository $catalog,
    ) {
    }

    #[Route('/api/v1/installer/status', name: 'api_installer_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        if (!$this->connections->isConfigured()) {
            return new JsonResponse(['status' => 'unconfigured', 'database' => false]);
        }

        return new JsonResponse([
            'status' => $this->installState->status(),
            'database' => true,
        ]);
    }

    #[Route('/api/v1/installer/bootstrap', name: 'api_installer_bootstrap', methods: ['POST'])]
    public function bootstrap(Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        if (!$this->connections->isConfigured()) {
            return new JsonResponse(['error' => 'database_unconfigured'], 503);
        }
        $connection = $this->connections->create();
        $this->schemaBootstrap->apply($connection);
        $this->installState->setStep('schema_ready');

        return new JsonResponse(['status' => 'schema_ready']);
    }

    #[Route('/api/v1/installer/complete', name: 'api_installer_complete', methods: ['POST'])]
    public function complete(Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        $baselineCore = $this->catalog->loadForInstallerCore();
        $plan = $this->planService->buildPlanFromCatalog([], PHP_VERSION, '8.0');
        if (!$plan->compatibility->compatible) {
            return new JsonResponse($plan->toArray(), 422);
        }
        try {
            $operationId = $this->orchestrator->installFromCatalog($plan, ['karkas/core'], 'installer', [
                'karkas/core' => $baselineCore->version->canonical,
            ]);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse(['status' => 'completed', 'operation_id' => $operationId]);
    }
}

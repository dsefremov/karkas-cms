<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Package\Lifecycle\AtomicInstallOrchestrator;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class PlatformInstallController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly PlatformPlanService $planService,
        private readonly AtomicInstallOrchestrator $installer,
    ) {
    }

    #[Route('/api/v1/platform/install', name: 'api_platform_install', methods: ['POST'])]
    public function install(Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        $body = json_decode($request->getContent(), true);
        if (!is_array($body)) {
            return new JsonResponse(['error' => 'invalid_request'], 400);
        }

        $includeCore = (bool) ($body['include_core'] ?? false);
        /** @var list<string> $modules */
        $modules = isset($body['modules']) && is_array($body['modules'])
            ? array_values(array_filter($body['modules'], is_string(...)))
            : [];
        if (!$includeCore && [] === $modules) {
            return new JsonResponse(['error' => 'invalid_request'], 400);
        }
        $plan = $includeCore
            ? $this->planService->buildExplicitCoreUpgradePlan($modules, PHP_VERSION, '8.0')
            : $this->planService->buildPlanFromCatalog($modules, PHP_VERSION, '8.0');
        if ($includeCore && !in_array('karkas/core', $modules, true)) {
            $modules[] = 'karkas/core';
        }
        if (!$plan->compatibility->compatible) {
            return new JsonResponse($plan->toArray(), 422);
        }

        try {
            $operationId = $this->installer->installFromCatalog($plan, $modules, 'admin');
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'plan' => $plan->toArray()], 409);
        }

        return new JsonResponse([
            'operation_id' => $operationId,
            'status' => 'succeeded',
            'plan' => $plan->toArray(),
        ], 202);
    }
}

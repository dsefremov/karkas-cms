<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Package\Plan\PlatformPlanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PlatformPlanController extends AbstractController
{
    public function __construct(
        private readonly PlatformPlanService $planService,
        private readonly AdminAuthService $adminAuth,
    ) {
    }

    #[Route('/api/v1/platform/plan', name: 'api_platform_plan_get', methods: ['GET'])]
    public function getPlan(Request $request): JsonResponse
    {
        $this->adminAuth->assertAuthenticated();
        /** @var list<string> $modules */
        $modules = array_values(array_filter($request->query->all('modules'), is_string(...)));
        $includeCore = filter_var($request->query->get('include_core', '0'), FILTER_VALIDATE_BOOLEAN);
        if ($includeCore) {
            $result = $this->planService->buildExplicitCoreUpgradePlan($modules, PHP_VERSION, '8.0');
        } else {
            if ([] === $modules) {
                $modules = ['karkas/blog'];
            }
            $result = $this->planService->buildPlanFromCatalog($modules, PHP_VERSION, '8.0');
        }

        return new JsonResponse($result->toArray(), $result->compatibility->compatible ? 200 : 422);
    }

    #[Route('/api/v1/platform/plan', name: 'api_platform_plan_post', methods: ['POST'])]
    public function postPlan(Request $request): JsonResponse
    {
        $this->adminAuth->assertAuthenticated();
        $body = json_decode($request->getContent(), true);
        if (!is_array($body) || !isset($body['modules']) || !is_array($body['modules'])) {
            return new JsonResponse(['error' => 'invalid_request'], 400);
        }
        $targets = [];
        foreach ($body['modules'] as $module) {
            if (!is_string($module)) {
                continue;
            }
            $targets[] = ['package_id' => $module, 'catalog' => true];
        }
        $result = $this->planService->buildPlanFromTargets($targets, PHP_VERSION, '8.0');

        return new JsonResponse($result->toArray(), $result->compatibility->compatible ? 200 : 422);
    }
}

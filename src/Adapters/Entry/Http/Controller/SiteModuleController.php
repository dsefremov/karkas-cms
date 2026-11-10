<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Package\Lifecycle\SiteModuleEnableService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class SiteModuleController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly SiteModuleEnableService $enableService,
    ) {
    }

    #[Route('/api/v1/sites/default/modules/karkas/blog/enable', name: 'api_site_enable_blog', methods: ['POST'])]
    public function enableBlog(Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        $body = json_decode($request->getContent(), true);
        $label = is_array($body) && isset($body['site_label']) && is_string($body['site_label'])
            ? $body['site_label']
            : 'Каркас — демо';
        try {
            $operationId = $this->enableService->enableBlogOnDefaultSite('admin', $label);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse(['status' => 'enabled', 'operation_id' => $operationId], 202);
    }
}

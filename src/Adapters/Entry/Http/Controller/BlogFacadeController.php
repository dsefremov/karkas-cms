<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Blog\BlogFacadeService;
use Karkas\Domain\Content\SitePageException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class BlogFacadeController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly BlogFacadeService $facade,
    ) {
    }

    #[Route('/api/v1/sites/{siteId}/modules/karkas/blog/facade', name: 'api_blog_facade_status', methods: ['GET'], requirements: ['siteId' => '\d+'])]
    public function status(int $siteId): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        $state = $this->facade->describeForSite($siteId);
        if (null === $state) {
            return new JsonResponse(['error' => 'blog_not_enabled'], 404);
        }

        return new JsonResponse(['facade' => $state]);
    }

    #[Route('/api/v1/sites/{siteId}/modules/karkas/blog/bind-page', name: 'api_blog_bind_page', methods: ['POST'], requirements: ['siteId' => '\d+'])]
    public function bind(int $siteId, Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        $body = json_decode($request->getContent(), true);
        $pageId = is_array($body) && isset($body['page_id']) && is_string($body['page_id']) ? $body['page_id'] : '';
        if ('' === $pageId) {
            return new JsonResponse(['error' => 'page_id_required'], 400);
        }
        try {
            $this->facade->bindPage($siteId, $pageId);
        } catch (SitePageException $e) {
            return new JsonResponse(['error' => $e->errorCode(), 'message' => $e->getMessage()], 409);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse(['status' => 'bound', 'facade' => $this->facade->describeForSite($siteId)]);
    }

    #[Route('/api/v1/sites/{siteId}/modules/karkas/blog/unbind-page', name: 'api_blog_unbind_page', methods: ['POST'], requirements: ['siteId' => '\d+'])]
    public function unbind(int $siteId, Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        try {
            $this->facade->unbindPage($siteId);
        } catch (RuntimeException $e) {
            $status = 'blog_not_bound' === $e->getMessage() ? 409 : 404;

            return new JsonResponse(['error' => $e->getMessage()], $status);
        }

        return new JsonResponse(['status' => 'unbound', 'facade' => $this->facade->describeForSite($siteId)]);
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Content\SitePageService;
use Karkas\Domain\Content\SitePageException;
use Karkas\Domain\Content\SitePageTree;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class SitePageController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly SitePageService $pages,
    ) {
    }

    #[Route('/api/v1/sites/{siteId}/pages/tree', name: 'api_site_pages_tree', methods: ['GET'])]
    public function tree(int $siteId): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        return new JsonResponse([
            'site_id' => $siteId,
            'tree' => $this->pages->tree($siteId),
        ]);
    }

    #[Route('/api/v1/sites/{siteId}/pages', name: 'api_site_pages_create', methods: ['POST'])]
    public function create(int $siteId, Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        $body = json_decode($request->getContent(), true);
        if (!is_array($body)) {
            return new JsonResponse(['error' => 'invalid_request'], 400);
        }
        try {
            $page = $this->pages->create($siteId, [
                'title' => (string) ($body['title'] ?? ''),
                'slug' => (string) ($body['slug'] ?? ''),
                'body_text' => (string) ($body['body_text'] ?? ''),
                'activity' => (string) ($body['activity'] ?? 'inactive'),
                'parent_id' => isset($body['parent_id']) ? (string) $body['parent_id'] : SitePageTree::SENTINEL_PARENT_ID,
            ]);
        } catch (SitePageException $e) {
            return new JsonResponse(['error' => $e->errorCode(), 'message' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse($page, 201);
    }

    #[Route('/api/v1/pages/{pageId}/save', name: 'api_page_save', methods: ['POST'])]
    public function save(string $pageId, Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        $body = json_decode($request->getContent(), true);
        if (!is_array($body)) {
            return new JsonResponse(['error' => 'invalid_request'], 400);
        }
        try {
            $page = $this->pages->save($pageId, [
                'title' => (string) ($body['title'] ?? ''),
                'slug' => (string) ($body['slug'] ?? ''),
                'body_text' => (string) ($body['body_text'] ?? ''),
                'activity' => (string) ($body['activity'] ?? 'inactive'),
            ]);
        } catch (SitePageException $e) {
            return new JsonResponse(['error' => $e->errorCode(), 'message' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse($page);
    }

    #[Route('/api/v1/pages/{pageId}', name: 'api_page_delete', methods: ['DELETE'])]
    public function delete(string $pageId): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        try {
            $this->pages->delete($pageId);
        } catch (SitePageException $e) {
            return new JsonResponse(['error' => $e->errorCode(), 'message' => $e->getMessage()], 409);
        }

        return new JsonResponse(['deleted' => true]);
    }

    #[Route('/api/v1/pages/{pageId}/move-preview', name: 'api_page_move_preview', methods: ['POST'])]
    public function movePreview(string $pageId, Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        $body = json_decode($request->getContent(), true);
        if (!is_array($body)) {
            return new JsonResponse(['error' => 'invalid_request'], 400);
        }
        try {
            $preview = $this->pages->movePreview(
                $pageId,
                (string) ($body['parent_id'] ?? ''),
                isset($body['slug']) ? (string) $body['slug'] : null,
            );
        } catch (SitePageException $e) {
            return new JsonResponse(['error' => $e->errorCode(), 'message' => $e->getMessage()], 409);
        }

        return new JsonResponse($preview);
    }

    #[Route('/api/v1/pages/{pageId}/move', name: 'api_page_move', methods: ['POST'])]
    public function move(string $pageId, Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        $body = json_decode($request->getContent(), true);
        if (!is_array($body)) {
            return new JsonResponse(['error' => 'invalid_request'], 400);
        }
        try {
            $result = $this->pages->move(
                $pageId,
                (string) ($body['parent_id'] ?? ''),
                (int) ($body['sort_order'] ?? 0),
                isset($body['slug']) ? (string) $body['slug'] : null,
            );
        } catch (SitePageException $e) {
            return new JsonResponse(['error' => $e->errorCode(), 'message' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse($result);
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Blog\BlogPostSaveService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class BlogPostController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly BlogPostSaveService $posts,
    ) {
    }

    #[Route('/api/v1/blog/posts', name: 'api_blog_posts_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
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
            $result = $this->posts->createDraft([
                'title' => (string) ($body['title'] ?? 'Черновик'),
                'slug' => (string) ($body['slug'] ?? 'draft-'.bin2hex(random_bytes(4))),
                'body_text' => (string) ($body['body_text'] ?? ''),
                'activity' => (string) ($body['activity'] ?? 'inactive'),
            ]);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse($result, 201);
    }

    #[Route('/api/v1/blog/posts/{postId}/save', name: 'api_blog_posts_save', methods: ['POST'])]
    public function save(string $postId, Request $request): JsonResponse
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
            $result = $this->posts->save($postId, [
                'title' => (string) ($body['title'] ?? ''),
                'slug' => (string) ($body['slug'] ?? ''),
                'body_text' => (string) ($body['body_text'] ?? ''),
                'activity' => (string) ($body['activity'] ?? 'inactive'),
            ]);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse($result);
    }

    #[Route('/api/v1/blog/posts/{postId}/versions', name: 'api_blog_posts_versions', methods: ['GET'])]
    public function versions(string $postId): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        try {
            $versions = $this->posts->listVersionHistory($postId);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse([
            'post_id' => $postId,
            'max_versions' => BlogPostSaveService::MAX_VERSION_HISTORY,
            'versions' => $versions,
        ]);
    }
}

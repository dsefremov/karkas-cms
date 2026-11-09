<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class AdminSessionController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
    ) {
    }

    #[Route('/api/v1/admin/session', name: 'api_admin_session_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse([
            'authenticated' => $this->adminAuth->isAuthenticated(),
            'configured' => $this->adminAuth->isConfigured(),
        ]);
    }

    #[Route('/api/v1/admin/session', name: 'api_admin_session_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        if (!is_array($body) || !isset($body['password']) || !is_string($body['password'])) {
            return new JsonResponse(['error' => 'invalid_request'], 400);
        }
        if (!$this->adminAuth->login($body['password'])) {
            return new JsonResponse(['error' => 'invalid_credentials'], 401);
        }

        return new JsonResponse(['authenticated' => true]);
    }

    #[Route('/api/v1/admin/session', name: 'api_admin_session_logout', methods: ['DELETE'])]
    public function logout(): JsonResponse
    {
        $this->adminAuth->logout();

        return new JsonResponse(['authenticated' => false]);
    }

    #[Route('/api/v1/admin/reauth', name: 'api_admin_reauth', methods: ['POST'])]
    public function reauth(Request $request): JsonResponse
    {
        try {
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        return new JsonResponse(['reauthenticated' => true]);
    }
}

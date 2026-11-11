<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Adapters\Infrastructure\Persistence\PlatformSiteRepository;
use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SitesController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly PlatformSiteRepository $sites,
    ) {
    }

    #[Route('/api/v1/sites', name: 'api_sites_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->adminAuth->assertAuthenticated();
        $rows = $this->sites->listAll();

        return new JsonResponse([
            'schema' => 'karkas-sites/v1',
            'sites' => array_map(static fn (array $site): array => [
                'id' => $site['id'],
                'site_key' => $site['site_key'],
                'label' => $site['label'],
                'is_default' => $site['is_default'],
                'canonical_host' => $site['canonical_host'],
            ], $rows),
        ]);
    }

    #[Route('/api/v1/sites', name: 'api_sites_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
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
        $siteKey = isset($body['site_key']) && is_string($body['site_key']) ? $body['site_key'] : '';
        $label = isset($body['label']) && is_string($body['label']) ? trim($body['label']) : '';
        $host = isset($body['canonical_host']) && is_string($body['canonical_host']) ? trim($body['canonical_host']) : null;
        if ('' === $siteKey || '' === $label) {
            return new JsonResponse(['error' => 'invalid_request'], 400);
        }
        $isDefault = isset($body['is_default']) && filter_var($body['is_default'], FILTER_VALIDATE_BOOLEAN);
        try {
            $site = $this->sites->create($siteKey, $label, $host, $isDefault);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }

        return new JsonResponse(['site' => $site], 201);
    }
}

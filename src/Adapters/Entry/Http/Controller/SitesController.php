<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Adapters\Infrastructure\Persistence\PlatformSiteRepository;
use Karkas\Adapters\Infrastructure\Persistence\SiteModuleRepository;
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
        private readonly SiteModuleRepository $siteModules,
    ) {
    }

    #[Route('/api/v1/sites', name: 'api_sites_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        $rows = $this->sites->listAll();
        $blogEnabled = $this->siteModules->blogEnabledBySiteIds();

        return new JsonResponse([
            'schema' => 'karkas-sites/v1',
            'sites' => array_map(static fn (array $site): array => [
                'id' => $site['id'],
                'site_key' => $site['site_key'],
                'label' => $site['label'],
                'is_default' => $site['is_default'],
                'canonical_host' => $site['canonical_host'],
                'blog_enabled' => $blogEnabled[$site['id']] ?? false,
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

    #[Route('/api/v1/sites/{siteId}/make-default', name: 'api_sites_make_default', methods: ['POST'])]
    public function makeDefault(int $siteId, Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        try {
            $this->sites->transferDefault($siteId);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'message' => 'Не удалось назначить сайт по умолчанию.'], 409);
        }
        $site = $this->sites->findById($siteId);

        return new JsonResponse(['site' => $site]);
    }

    #[Route('/api/v1/sites/{siteId}', name: 'api_sites_delete', methods: ['DELETE'])]
    public function delete(int $siteId, Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        try {
            $this->sites->delete($siteId);
        } catch (RuntimeException $e) {
            $message = 'default_site_delete_forbidden' === $e->getMessage()
                ? 'Сайт по умолчанию нельзя удалить. Сначала назначьте другой сайт по умолчанию.'
                : 'Не удалось удалить сайт.';
            $status = 'default_site_delete_forbidden' === $e->getMessage() ? 409 : 404;

            return new JsonResponse(['error' => $e->getMessage(), 'message' => $message], $status);
        }

        return new JsonResponse(['deleted' => true]);
    }
}

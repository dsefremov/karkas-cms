<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Admin\ZipUploadGate;
use Karkas\Application\Package\Quarantine\FolderIngestService;
use Karkas\Application\Package\Quarantine\UnsafeArchiveException;
use Karkas\Application\Package\Quarantine\ZipIngestService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PackageDeliveryController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly ZipUploadGate $zipUploadGate,
        private readonly ZipIngestService $zipIngest,
        private readonly FolderIngestService $folderIngest,
    ) {
    }

    #[Route('/api/v1/admin/packages/zip-upload', name: 'api_packages_zip_upload', methods: ['POST'])]
    public function uploadZip(Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
            $this->zipUploadGate->assertEnabled();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        }

        $file = $request->files->get('package');
        if (null === $file || !$file->isValid()) {
            return new JsonResponse(['error' => 'missing_package_file'], 400);
        }
        try {
            $staged = $this->zipIngest->ingestUploadedZip($file->getPathname());
        } catch (UnsafeArchiveException $e) {
            return new JsonResponse(['error' => 'unsafe_archive', 'reasons' => $e->reasons], 422);
        }

        return new JsonResponse([
            'package_id' => $staged->manifest->componentId->value,
            'version' => $staged->manifest->version->canonical,
            'staging_path' => $staged->stagingRelativePath,
        ], 201);
    }

    #[Route('/api/v1/admin/packages/zip-upload', name: 'api_packages_zip_status', methods: ['GET'])]
    public function zipStatus(): JsonResponse
    {
        $this->adminAuth->assertAuthenticated();

        return new JsonResponse(['enabled' => $this->zipUploadGate->isEnabled()]);
    }

    #[Route('/api/v1/admin/packages/scan-incoming', name: 'api_packages_scan_incoming', methods: ['POST'])]
    public function scanIncoming(Request $request): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
            $this->adminAuth->resolveReauthFromRequest($request);
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        $staged = $this->folderIngest->ingestReadyFolders();

        return new JsonResponse([
            'ingested' => array_map(static fn ($p) => [
                'package_id' => $p->manifest->componentId->value,
                'version' => $p->manifest->version->canonical,
            ], $staged),
        ]);
    }
}

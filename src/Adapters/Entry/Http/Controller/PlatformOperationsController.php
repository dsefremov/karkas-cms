<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Adapters\Infrastructure\Persistence\OperationJournalRepository;
use Karkas\Application\Admin\AdminAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PlatformOperationsController extends AbstractController
{
    public function __construct(
        private readonly OperationJournalRepository $journal,
        private readonly AdminAuthService $adminAuth,
    ) {
    }

    #[Route('/api/v1/admin/platform/operations', name: 'api_platform_operations', methods: ['GET'])]
    public function listOperations(): JsonResponse
    {
        $this->adminAuth->assertAuthenticated();

        return new JsonResponse(['operations' => $this->journal->listRecent()]);
    }
}

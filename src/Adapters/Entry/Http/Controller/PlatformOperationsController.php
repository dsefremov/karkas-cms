<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Adapters\Infrastructure\Persistence\OperationJournalRepository;
use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Admin\OperationJournalPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PlatformOperationsController extends AbstractController
{
    public function __construct(
        private readonly OperationJournalRepository $journal,
        private readonly AdminAuthService $adminAuth,
        private readonly OperationJournalPresenter $presenter,
    ) {
    }

    #[Route('/api/v1/admin/platform/operations', name: 'api_platform_operations', methods: ['GET'])]
    public function listOperations(): JsonResponse
    {
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        $rows = $this->journal->listRecent();
        $operations = array_map(fn (array $row): array => $this->presenter->present($row), $rows);

        return new JsonResponse(['operations' => $operations]);
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Admin\AdminAuthException;
use Karkas\Application\Admin\AdminAuthService;
use Karkas\Application\Admin\AdminDemoSeedService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class AdminDemoSeedController extends AbstractController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly AdminDemoSeedService $demoSeed,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
    ) {
    }

    #[Route('/api/v1/admin/demo/seed', name: 'api_admin_demo_seed', methods: ['POST'])]
    public function seed(): JsonResponse
    {
        if (!$this->debug && '1' !== getenv('KARKAS_ALLOW_DEMO_SEED')) {
            return new JsonResponse(['error' => 'forbidden'], 403);
        }
        try {
            $this->adminAuth->assertAuthenticated();
        } catch (AdminAuthException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
        try {
            return new JsonResponse($this->demoSeed->seed());
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }
    }
}

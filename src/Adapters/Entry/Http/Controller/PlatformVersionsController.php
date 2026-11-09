<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Controller;

use Karkas\Application\Package\Inventory\PlatformVersionInventory;
use Karkas\Domain\Platform\Version;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PlatformVersionsController extends AbstractController
{
    public function __construct(
        private readonly PlatformVersionInventory $inventory,
    ) {
    }

    #[Route('/api/v1/platform/versions', name: 'api_platform_versions', methods: ['GET'])]
    public function versions(): JsonResponse
    {
        $slots = $this->inventory->buildInventory(PHP_VERSION, '8.0');

        return new JsonResponse([
            'schema' => 'karkas-platform-versions/v1',
            'stage' => (int) Version::STAGE,
            'sdk_version' => $this->inventory->sdkVersion(),
            'packages' => array_map(static fn ($slot) => $slot->toArray(), $slots),
            'notes' => [
                'available_metadata_untrusted' => true,
                'online_version_check' => 'phase_2',
            ],
        ]);
    }
}

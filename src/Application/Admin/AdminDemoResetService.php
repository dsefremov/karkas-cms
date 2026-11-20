<?php

declare(strict_types=1);

namespace Karkas\Application\Admin;

use Doctrine\DBAL\Connection;
use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSiteRepository;

/**
 * Dev/test: remove multisite clutter from e2e, then re-seed demo content.
 */
final class AdminDemoResetService
{
    public function __construct(
        private readonly PlatformSiteRepository $sites,
        private readonly AdminDemoSeedService $demoSeed,
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    /**
     * @return array{sites_removed: int, seed: array<string, mixed>}
     */
    public function resetAndSeed(): array
    {
        $removed = $this->purgeNonDefaultSites();
        $seed = $this->demoSeed->seed();

        return [
            'sites_removed' => $removed,
            'seed' => $seed,
        ];
    }

    private function purgeNonDefaultSites(): int
    {
        $connection = $this->connections->create();
        $rows = $connection->fetchAllAssociative(
            'SELECT id, is_default FROM platform_site WHERE is_default = 0 ORDER BY id ASC',
        );
        $removed = 0;
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            try {
                $this->sites->delete($id);
                ++$removed;
            } catch (\Throwable) {
                $this->deleteSiteRow($connection, $id);
                ++$removed;
            }
        }

        return $removed;
    }

    private function deleteSiteRow(Connection $connection, int $siteId): void
    {
        $connection->delete('platform_site', ['id' => $siteId]);
    }
}
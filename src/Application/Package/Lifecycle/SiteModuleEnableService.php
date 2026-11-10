<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Lifecycle;

use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\MaintenanceModeRepository;
use Karkas\Adapters\Infrastructure\Persistence\ModuleInstallationRepository;
use Karkas\Adapters\Infrastructure\Persistence\ModuleInstanceRepository;
use Karkas\Adapters\Infrastructure\Persistence\ModuleMountRepository;
use Karkas\Adapters\Infrastructure\Persistence\OperationJournalRepository;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSiteRepository;
use Karkas\Adapters\Infrastructure\Persistence\SiteModuleRepository;
use Karkas\Application\Package\Manifest\ManifestJsonParser;
use RuntimeException;
use Throwable;

final class SiteModuleEnableService
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
        private readonly PlatformSiteRepository $sites,
        private readonly ModuleInstallationRepository $installations,
        private readonly SiteModuleRepository $siteModules,
        private readonly ModuleInstanceRepository $instances,
        private readonly ModuleMountRepository $mounts,
        private readonly ModuleMigrationRunner $migrationRunner,
        private readonly ManifestJsonParser $parser,
        private readonly OperationJournalRepository $journal,
        private readonly MaintenanceModeRepository $maintenance,
        private readonly string $packagesRoot,
    ) {
    }

    public function enableBlogOnDefaultSite(string $actor, string $siteLabel = 'Каркас — демо'): string
    {
        return $this->enable('karkas/blog', '/blog', $actor, $siteLabel);
    }

    public function enable(string $packageId, string $mountPrefix, string $actor, string $siteLabel): string
    {
        $installed = $this->installations->find($packageId);
        if (null === $installed) {
            throw new RuntimeException(sprintf('Package "%s" is not installed globally.', $packageId));
        }

        $operationId = bin2hex(random_bytes(16));
        $this->journal->create($operationId, 'module.site_enable', 'running', 'enable', $actor, null, [
            'package_id' => $packageId,
            'mount' => $mountPrefix,
        ]);
        $this->maintenance->setEnabled(true, 'Module enable in progress');

        try {
            $this->sites->ensureDefaultSite($siteLabel);
            $siteId = PlatformSiteRepository::DEFAULT_SITE_ID;

            $stagingRoot = rtrim($this->packagesRoot, '/').'/staging/'.$installed['staging_relative_path'];
            $manifestPath = $stagingRoot.'/manifest.json';
            if (!is_file($manifestPath)) {
                throw new RuntimeException('Staged manifest missing for module enable.');
            }
            $manifest = $this->parser->parse((string) file_get_contents($manifestPath));

            $connection = $this->connections->create();
            $this->migrationRunner->applyPending($connection, $stagingRoot, $manifest);
            $instanceId = bin2hex(random_bytes(16));
            $existing = $this->instances->findBySiteModule($siteId, $packageId);
            if (null === $existing) {
                $this->instances->create($instanceId, $siteId, $packageId);
            } else {
                $instanceId = $existing['id'];
            }
            $this->siteModules->upsert($siteId, $packageId, 'enabled', $manifest->displayName ?? $packageId);
            $this->mounts->ensureDirectMount(bin2hex(random_bytes(16)), $siteId, $instanceId, $mountPrefix);
            $this->installations->updateLifecycleStatus($packageId, 'enabled');

            $this->journal->updateStatus($operationId, 'succeeded', 'completed');
        } catch (Throwable $e) {
            $this->journal->updateStatus($operationId, 'failed', 'error', $e->getMessage());
            throw $e;
        } finally {
            $this->maintenance->setEnabled(false, null);
        }

        return $operationId;
    }
}

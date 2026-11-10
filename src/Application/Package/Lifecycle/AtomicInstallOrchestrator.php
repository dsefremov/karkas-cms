<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Lifecycle;

use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
use Karkas\Adapters\Infrastructure\Persistence\InstallStateRepository;
use Karkas\Adapters\Infrastructure\Persistence\MaintenanceModeRepository;
use Karkas\Adapters\Infrastructure\Persistence\ModuleInstallationRepository;
use Karkas\Adapters\Infrastructure\Persistence\OperationJournalRepository;
use Karkas\Adapters\Infrastructure\Persistence\PlatformCoreStateRepository;
use Karkas\Adapters\Infrastructure\Persistence\RollbackSlotRepository;
use Karkas\Application\Package\Catalog\PackageCatalogRepository;
use Karkas\Application\Package\Compatibility\VersionTransitionPolicy;
use Karkas\Application\Package\Plan\PlatformPlanResult;
use Karkas\Domain\Package\Rollback\SavedDeploymentGeneration;
use Karkas\Domain\Platform\SemVerVersion;
use RuntimeException;
use Throwable;

final class AtomicInstallOrchestrator
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
        private readonly PackageStagingService $staging,
        private readonly PackageCatalogRepository $catalog,
        private readonly OperationJournalRepository $journal,
        private readonly MaintenanceModeRepository $maintenance,
        private readonly ModuleInstallationRepository $modules,
        private readonly PlatformCoreStateRepository $coreState,
        private readonly RollbackSlotRepository $rollbackSlot,
        private readonly InstallStateRepository $installState,
        private readonly VersionTransitionPolicy $transitionPolicy,
    ) {
    }

    /**
     * @param list<string>          $catalogPackageIds module ids to install/update from catalog (may include karkas/core)
     * @param array<string, string> $versionPins       package id => canonical version
     */
    public function installFromCatalog(PlatformPlanResult $plan, array $catalogPackageIds, string $actor, array $versionPins = []): string
    {
        if (!$plan->compatibility->compatible) {
            throw new RuntimeException('Refusing install: plan is not compatible.');
        }

        $operationId = bin2hex(random_bytes(16));
        $this->journal->create($operationId, 'package.install', 'queued', 'precheck', $actor, $plan->planDigest, [
            'packages' => $catalogPackageIds,
        ]);

        try {
            $this->maintenance->setEnabled(true, 'Package install in progress');
            $this->journal->updateStatus($operationId, 'running', 'maintenance');
            $this->captureRollbackSlot();

            $staged = [];
            foreach ($catalogPackageIds as $packageId) {
                $manifest = isset($versionPins[$packageId])
                    ? $this->catalog->loadAtVersion($packageId, new SemVerVersion($versionPins[$packageId]))
                    : $this->catalog->loadLatest($packageId);
                $source = $this->catalog->packageSourceDirectory($packageId, $manifest->version);
                $staged[] = $this->staging->stageFromDirectory($source);
            }

            $connection = $this->connections->create();
            $connection->beginTransaction();
            try {
                foreach ($staged as $package) {
                    if ($package->manifest->isCorePackage()) {
                        $current = $this->coreState->get();
                        $currentVersion = null !== $current ? new SemVerVersion((string) $current['version']) : null;
                        $this->transitionPolicy->assertUpgradeOrEqual('karkas/core', $currentVersion, $package->manifest->version);
                        $this->coreState->upsert($package->manifest->version, $package->manifestDigest, $package->stagingRelativePath);
                        continue;
                    }
                    $currentRow = $this->modules->find($package->manifest->componentId->value);
                    $currentVersion = null !== $currentRow ? new SemVerVersion((string) $currentRow['version']) : null;
                    $this->transitionPolicy->assertUpgradeOrEqual($package->manifest->componentId->value, $currentVersion, $package->manifest->version);
                    $this->modules->upsertInstalled(
                        $package->manifest->componentId->value,
                        $package->manifest->version,
                        $package->manifestDigest,
                        $package->stagingRelativePath,
                    );
                }
                $connection->commit();
            } catch (Throwable $e) {
                $connection->rollBack();
                throw $e;
            }

            if ('completed' !== $this->installState->status()) {
                $this->installState->markCompleted();
            }

            $this->journal->updateStatus($operationId, 'succeeded', 'completed');
        } catch (Throwable $e) {
            $this->journal->updateStatus($operationId, 'failed', 'error', $e->getMessage());
            throw $e;
        } finally {
            $this->maintenance->setEnabled(false, null);
        }

        return $operationId;
    }

    public function executeRollback(string $actor): void
    {
        $slot = $this->rollbackSlot->getSlot();
        if (null === $slot) {
            throw new RuntimeException('No saved generation available for rollback.');
        }

        $operationId = bin2hex(random_bytes(16));
        $this->journal->create($operationId, 'platform.rollback', 'running', 'rollback', $actor, $slot->planDigest, []);
        $this->maintenance->setEnabled(true, 'Rollback in progress');

        try {
            $this->transitionPolicy->assertControlledRollbackTarget($slot, $slot->coreVersion, $slot);

            $connection = $this->connections->create();
            $connection->beginTransaction();
            try {
                $this->applyRollbackRegistry($connection, $slot);
                $connection->commit();
            } catch (Throwable $e) {
                $connection->rollBack();
                throw $e;
            }
            $this->journal->updateStatus($operationId, 'succeeded', 'completed');
        } catch (Throwable $e) {
            $this->journal->updateStatus($operationId, 'failed', 'error', $e->getMessage());
            throw $e;
        } finally {
            $this->maintenance->setEnabled(false, null);
        }
    }

    private function captureRollbackSlot(): void
    {
        $core = $this->coreState->get();
        $modules = $this->modules->allByPackageId();
        $moduleVersions = [];
        foreach ($modules as $packageId => $row) {
            $moduleVersions[$packageId] = new SemVerVersion((string) $row['version']);
        }
        $coreVersion = null !== $core
            ? new SemVerVersion((string) $core['version'])
            : new SemVerVersion('0.0.0');
        $generation = new SavedDeploymentGeneration(
            generationId: 'gen-'.bin2hex(random_bytes(8)),
            coreVersion: $coreVersion,
            moduleVersions: $moduleVersions,
            planDigest: hash('sha256', json_encode(['core' => $coreVersion->canonical, 'modules' => $moduleVersions], JSON_THROW_ON_ERROR)),
            savedAt: new DateTimeImmutable(),
        );
        $this->rollbackSlot->saveSlot($generation);
    }

    private function applyRollbackRegistry(Connection $connection, SavedDeploymentGeneration $slot): void
    {
        // Registry-only rollback in Stage 2: restore version metadata recorded in the slot (paths remain on disk).
        $connection->executeStatement(
            'UPDATE platform_core_state SET version = :version, updated_at = :now WHERE singleton_id = 1',
            ['version' => $slot->coreVersion->canonical, 'now' => new DateTime()],
        );
        foreach ($slot->moduleVersions as $packageId => $version) {
            $connection->executeStatement(
                'UPDATE module_installation SET version = :version, updated_at = :now WHERE package_id = :id',
                ['version' => $version->canonical, 'now' => new DateTime(), 'id' => $packageId],
            );
        }
    }
}

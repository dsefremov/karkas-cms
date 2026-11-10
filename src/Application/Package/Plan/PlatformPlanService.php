<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Plan;

use Karkas\Adapters\Infrastructure\Persistence\ModuleInstallationRepository;
use Karkas\Adapters\Infrastructure\Persistence\PlatformCoreStateRepository;
use Karkas\Application\Package\Catalog\PackageCatalogRepository;
use Karkas\Application\Package\Compatibility\DowngradeRefusalException;
use Karkas\Application\Package\Compatibility\VersionTransitionPolicy;
use Karkas\Application\Package\Compatibility\WholePlanCompatibilityChecker;
use Karkas\Application\Package\Compatibility\WholePlanCompatibilityResult;
use Karkas\Application\Package\Manifest\ManifestDigestService;
use Karkas\Application\Package\Quarantine\IncomingPackageScanService;
use Karkas\Domain\Package\Manifest\PackageManifest;
use Karkas\Domain\Platform\SemVerVersion;

final class PlatformPlanService
{
    public function __construct(
        private readonly PackageCatalogRepository $catalog,
        private readonly WholePlanCompatibilityChecker $planChecker,
        private readonly VersionTransitionPolicy $transitionPolicy,
        private readonly ManifestDigestService $digestService,
        private readonly PlatformCoreStateRepository $coreState,
        private readonly ModuleInstallationRepository $moduleInstallations,
        private readonly IncomingPackageScanService $incomingScan,
        private readonly string $fallbackCoreVersion,
    ) {
    }

    /**
     * @param list<string> $modulePackageIds from catalog (e.g. karkas/blog)
     */
    public function buildPlanFromCatalog(array $modulePackageIds, string $targetPhp, string $targetMysql): PlatformPlanResult
    {
        $incoming = $this->incomingScan->scanAndIngest();
        $candidates = [];
        foreach ($modulePackageIds as $packageId) {
            $candidates[] = $this->catalog->loadLatest($packageId);
        }

        return $this->evaluate($candidates, $targetPhp, $targetMysql, $incoming);
    }

    /**
     * @param list<string> $modulePackageIds
     */
    public function buildExplicitCoreUpgradePlan(array $modulePackageIds, string $targetPhp, string $targetMysql): PlatformPlanResult
    {
        $incoming = $this->incomingScan->scanAndIngest();
        $candidates = [$this->catalog->loadLatest('karkas/core')];
        foreach ($modulePackageIds as $packageId) {
            if ('karkas/core' === $packageId) {
                continue;
            }
            $candidates[] = $this->catalog->loadLatest($packageId);
        }

        return $this->evaluate($candidates, $targetPhp, $targetMysql, $incoming);
    }

    /**
     * @param list<array{package_id: string, catalog: bool}> $targets
     */
    public function buildPlanFromTargets(array $targets, string $targetPhp, string $targetMysql): PlatformPlanResult
    {
        $candidates = [];
        foreach ($targets as $target) {
            $candidates[] = $this->catalog->loadLatest($target['package_id']);
        }

        return $this->evaluate($candidates, $targetPhp, $targetMysql, ['discovered' => [], 'staged_package_ids' => []]);
    }

    /**
     * @param list<PackageManifest> $candidates
     * @param array<string, mixed>  $incoming
     */
    private function evaluate(array $candidates, string $targetPhp, string $targetMysql, array $incoming): PlatformPlanResult
    {
        $coreManifest = $this->catalog->loadLatest('karkas/core');
        $coreRow = $this->coreState->get();
        $currentCoreVersion = null !== $coreRow
            ? new SemVerVersion((string) $coreRow['version'])
            : new SemVerVersion($this->fallbackCoreVersion);

        $coreForPlan = new PackageManifest(
            format: $coreManifest->format,
            componentId: $coreManifest->componentId,
            componentType: $coreManifest->componentType,
            version: $currentCoreVersion,
            requiresCore: '',
            requiresSdk: $coreManifest->requiresSdk,
            requiresPhp: $coreManifest->requiresPhp,
            requiresMysqlServer: $coreManifest->requiresMysqlServer,
            requiredExtensions: $coreManifest->requiredExtensions,
            dependencies: $coreManifest->dependencies,
            conflicts: $coreManifest->conflicts,
            migrations: $coreManifest->migrations,
            files: $coreManifest->files,
            manifestDigest: $coreManifest->manifestDigest,
            displayName: $coreManifest->displayName,
        );

        $installed = [];
        foreach ($this->moduleInstallations->allByPackageId() as $packageId => $row) {
            $installed[$packageId] = $this->catalog->loadLatest($packageId);
        }

        $errors = [];
        foreach ($candidates as $candidate) {
            if ($candidate->isCorePackage()) {
                $current = null !== $coreRow ? new SemVerVersion((string) $coreRow['version']) : null;
                try {
                    $this->transitionPolicy->assertUpgradeOrEqual('karkas/core', $current, $candidate->version);
                } catch (DowngradeRefusalException $e) {
                    $errors[] = $e->getMessage();
                }
                continue;
            }
            $currentRow = $this->moduleInstallations->find($candidate->componentId->value);
            $current = null !== $currentRow ? new SemVerVersion((string) $currentRow['version']) : null;
            try {
                $this->transitionPolicy->assertUpgradeOrEqual($candidate->componentId->value, $current, $candidate->version);
            } catch (DowngradeRefusalException $e) {
                $errors[] = $e->getMessage();
            }
        }

        $compatibility = $this->planChecker->evaluatePlan(
            $coreForPlan,
            $installed,
            $candidates,
            $targetPhp,
            $targetMysql,
        );

        $allErrors = array_merge($errors, $compatibility->errors);
        $compatible = $compatibility->compatible && [] === $errors;

        $planPayload = [
            'core_version' => $currentCoreVersion->canonical,
            'targets' => array_map(static fn (PackageManifest $m) => [
                'package_id' => $m->componentId->value,
                'version' => $m->version->canonical,
            ], $candidates),
            'compatible' => $compatible,
            'errors' => $allErrors,
            'incoming_scan' => $incoming,
        ];
        $digest = $this->digestService->normalizedDigest(json_encode($planPayload, JSON_THROW_ON_ERROR));

        return new PlatformPlanResult(
            $digest,
            $compatible ? WholePlanCompatibilityResult::ok() : WholePlanCompatibilityResult::failed($allErrors),
            $planPayload,
        );
    }
}

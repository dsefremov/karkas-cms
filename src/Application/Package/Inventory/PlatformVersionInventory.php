<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Inventory;

use Karkas\Application\Package\Compatibility\WholePlanCompatibilityChecker;
use Karkas\Application\Package\Manifest\ManifestJsonParser;
use Karkas\Domain\Package\VersionAvailabilityStatus;
use Karkas\Domain\Package\VersionSourceKind;
use Karkas\Domain\Platform\SemVerVersion;
use Symfony\Component\Finder\Finder;
use Throwable;

/**
 * Stage 1 read model: current from deployment config, available from local catalog directory.
 */
final class PlatformVersionInventory
{
    public function __construct(
        private readonly string $coreVersion,
        private readonly string $sdkVersion,
        private readonly string $localCatalogDir,
        private readonly ManifestJsonParser $parser,
        private readonly WholePlanCompatibilityChecker $planChecker,
    ) {
    }

    /**
     * @return list<PackageVersionSlot>
     */
    public function buildInventory(string $targetPhpVersion, string $targetMysqlServer): array
    {
        $slots = [];
        $coreCurrent = new SemVerVersion($this->coreVersion);
        $slots[] = new PackageVersionSlot(
            packageId: 'karkas/core',
            current: $coreCurrent,
            available: $this->readCatalogVersion('karkas/core', $coreCurrent),
            target: null,
            sourceKind: VersionSourceKind::Local,
            status: VersionAvailabilityStatus::Untrusted,
            compatibilityReason: 'Metadata is local/untrusted until install plan validation.',
        );

        foreach ($this->discoverModuleCatalogEntries() as $moduleId) {
            $current = null;
            $available = $this->readCatalogVersion($moduleId, $current);
            $status = VersionAvailabilityStatus::Untrusted;
            $reason = 'Local catalog metadata is untrusted until install plan validation.';
            if (null !== $available && $this->isCatalogCompatible($moduleId, $available, $targetPhpVersion, $targetMysqlServer)) {
                $status = VersionAvailabilityStatus::Compatible;
                $reason = 'Candidate passes whole-plan checks against current core.';
            } elseif (null !== $available) {
                $status = VersionAvailabilityStatus::Incompatible;
                $reason = 'Candidate failed whole-plan compatibility.';
            }

            $slots[] = new PackageVersionSlot(
                packageId: $moduleId,
                current: $current,
                available: $available,
                target: null,
                sourceKind: VersionSourceKind::Local,
                status: $status,
                compatibilityReason: $reason,
            );
        }

        return $slots;
    }

    public function sdkVersion(): string
    {
        return $this->sdkVersion;
    }

    private function readCatalogVersion(string $packageId, ?SemVerVersion $current): ?SemVerVersion
    {
        $manifestPath = $this->localCatalogDir.'/'.$packageId.'/manifest.json';
        if (!is_file($manifestPath)) {
            return $current;
        }
        try {
            $manifest = $this->parser->parse((string) file_get_contents($manifestPath));

            return $manifest->version;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function discoverModuleCatalogEntries(): array
    {
        $result = [];
        if (!is_dir($this->localCatalogDir)) {
            return $result;
        }
        $finder = new Finder();
        $finder->files()->name('manifest.json')->in($this->localCatalogDir);
        foreach ($finder as $file) {
            $relativeDir = $file->getRelativePath();
            if ('' === $relativeDir) {
                continue;
            }
            $moduleId = str_replace(DIRECTORY_SEPARATOR, '/', $relativeDir);
            if ('karkas/core' === $moduleId) {
                continue;
            }
            $result[] = $moduleId;
        }

        return array_values(array_unique($result));
    }

    private function isCatalogCompatible(
        string $moduleId,
        SemVerVersion $available,
        string $targetPhp,
        string $targetMysql,
    ): bool {
        $coreManifestPath = $this->localCatalogDir.'/karkas/core/manifest.json';
        $moduleManifestPath = $this->localCatalogDir.'/'.$moduleId.'/manifest.json';
        if (!is_file($coreManifestPath) || !is_file($moduleManifestPath)) {
            return false;
        }
        try {
            $core = $this->parser->parse((string) file_get_contents($coreManifestPath));
            $core = new \Karkas\Domain\Package\Manifest\PackageManifest(
                format: $core->format,
                componentId: $core->componentId,
                componentType: $core->componentType,
                version: new SemVerVersion($this->coreVersion),
                requiresCore: $core->requiresCore,
                requiresSdk: $core->requiresSdk,
                requiresPhp: $core->requiresPhp,
                requiresMysqlServer: $core->requiresMysqlServer,
                requiredExtensions: $core->requiredExtensions,
                dependencies: $core->dependencies,
                conflicts: $core->conflicts,
                migrations: $core->migrations,
                files: $core->files,
                manifestDigest: $core->manifestDigest,
                displayName: $core->displayName,
            );
            $module = $this->parser->parse((string) file_get_contents($moduleManifestPath));
            $plan = $this->planChecker->evaluatePlan($core, [], [$module], $targetPhp, $targetMysql);

            return $plan->compatible;
        } catch (Throwable) {
            return false;
        }
    }
}

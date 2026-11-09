<?php

declare(strict_types=1);

namespace Karkas\Domain\ModuleSdk\Contract;

use Karkas\Application\Package\Compatibility\WholePlanCompatibilityResult;
use Karkas\Domain\Package\Manifest\PackageManifest;

/**
 * Whole-planned-set compatibility (Core + SDK + modules + environment constraints).
 */
interface WholePlanCompatibilityPort
{
    /**
     * @param array<string, PackageManifest> $installedByModuleId keyed by component id
     * @param list<PackageManifest>          $candidates          proposed installs/updates
     */
    public function evaluatePlan(
        PackageManifest $coreManifest,
        array $installedByModuleId,
        array $candidates,
        string $targetPhpVersion,
        string $targetMysqlServer,
    ): WholePlanCompatibilityResult;
}

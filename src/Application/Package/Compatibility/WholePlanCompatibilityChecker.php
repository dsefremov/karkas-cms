<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Compatibility;

use InvalidArgumentException;
use Karkas\Domain\ModuleSdk\Contract\WholePlanCompatibilityPort;
use Karkas\Domain\Package\Manifest\PackageManifest;
use Karkas\Domain\Platform\SemVerVersion;

final class WholePlanCompatibilityChecker implements WholePlanCompatibilityPort
{
    public function __construct(
        private readonly SemVerConstraintEvaluator $constraints,
    ) {
    }

    /**
     * @param array<string, PackageManifest> $installedByModuleId
     * @param list<PackageManifest>          $candidates
     */
    public function evaluatePlan(
        PackageManifest $coreManifest,
        array $installedByModuleId,
        array $candidates,
        string $targetPhpVersion,
        string $targetMysqlServer,
    ): WholePlanCompatibilityResult {
        $errors = [];
        $planned = $installedByModuleId;

        foreach ($candidates as $candidate) {
            $planned[$candidate->componentId->value] = $candidate;
        }

        if (!$coreManifest->isCorePackage()) {
            $errors[] = 'Plan requires a core package manifest.';
        }

        $targetPhp = SemVerVersion::tryParse($this->normalizePhpTriple($targetPhpVersion));
        if (null === $targetPhp) {
            $errors[] = sprintf('Target PHP version "%s" is not a valid SemVer triple.', $targetPhpVersion);
        }

        foreach ($planned as $manifest) {
            if ($manifest->isCorePackage()) {
                continue;
            }
            if (!$this->constraints->satisfies($coreManifest->version, $manifest->requiresCore)) {
                $errors[] = sprintf(
                    'Module %s requires core %s; planned core is %s.',
                    $manifest->componentId->value,
                    $manifest->requiresCore,
                    $coreManifest->version->canonical,
                );
            }
            if (null !== $targetPhp && !$this->constraints->satisfies($targetPhp, $manifest->requiresPhp)) {
                $errors[] = sprintf(
                    'Module %s requires PHP %s; target is %s.',
                    $manifest->componentId->value,
                    $manifest->requiresPhp,
                    $targetPhp->canonical,
                );
            }
            if (null !== $manifest->requiresMysqlServer
                && !$this->constraints->satisfies(
                    new SemVerVersion($this->normalizeMysqlTriple($targetMysqlServer)),
                    $manifest->requiresMysqlServer,
                )) {
                $errors[] = sprintf(
                    'Module %s requires MySQL %s; target is %s.',
                    $manifest->componentId->value,
                    $manifest->requiresMysqlServer,
                    $targetMysqlServer,
                );
            }

            foreach ($manifest->dependencies as $depId => $range) {
                if (!isset($planned[$depId])) {
                    $errors[] = sprintf('Missing dependency %s required by %s (%s).', $depId, $manifest->componentId->value, $range);
                    continue;
                }
                if (!$this->constraints->satisfies($planned[$depId]->version, $range)) {
                    $errors[] = sprintf(
                        'Dependency %s@%s does not satisfy %s required by %s.',
                        $depId,
                        $planned[$depId]->version->canonical,
                        $range,
                        $manifest->componentId->value,
                    );
                }
            }

            foreach ($manifest->conflicts as $conflictId => $range) {
                if (!isset($planned[$conflictId])) {
                    continue;
                }
                if ($this->constraints->satisfies($planned[$conflictId]->version, $range)) {
                    $errors[] = sprintf(
                        'Conflict %s@%s matches %s declared by %s.',
                        $conflictId,
                        $planned[$conflictId]->version->canonical,
                        $range,
                        $manifest->componentId->value,
                    );
                }
            }
        }

        return [] === $errors ? WholePlanCompatibilityResult::ok() : WholePlanCompatibilityResult::failed($errors);
    }

    private function normalizePhpTriple(string $phpVersion): string
    {
        if (preg_match('/^(\d+)\.(\d+)\.(\d+)/', $phpVersion, $m)) {
            return $m[1].'.'.$m[2].'.'.$m[3];
        }
        if (preg_match('/^(\d+)\.(\d+)/', $phpVersion, $m)) {
            return $m[1].'.'.$m[2].'.0';
        }

        throw new InvalidArgumentException('Cannot normalize PHP version.');
    }

    private function normalizeMysqlTriple(string $serverVersion): string
    {
        if (preg_match('/^(\d+)\.(\d+)/', $serverVersion, $m)) {
            return $m[1].'.'.$m[2].'.0';
        }

        return '8.0.0';
    }
}

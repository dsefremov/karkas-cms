<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Compatibility;

use Karkas\Domain\Package\Rollback\SavedDeploymentGeneration;
use Karkas\Domain\Platform\SemVerVersion;
use RuntimeException;

/**
 * Ordinary downgrade blocked; controlled rollback only to the single saved generation (spec §6).
 */
final class VersionTransitionPolicy
{
    public function __construct(
        private readonly SemVerConstraintEvaluator $evaluator,
    ) {
    }

    public function assertUpgradeOrEqual(
        string $packageId,
        ?SemVerVersion $current,
        SemVerVersion $target,
    ): void {
        if (null === $current) {
            return;
        }
        if ($this->evaluator->compare($target, $current) < 0) {
            throw new DowngradeRefusalException($packageId, $current, $target);
        }
    }

    public function assertControlledRollbackTarget(
        SavedDeploymentGeneration $saved,
        SemVerVersion $requestedCore,
        SavedDeploymentGeneration $currentSavedSlot,
    ): void {
        if ($saved->generationId !== $currentSavedSlot->generationId) {
            throw new RuntimeException('Rollback is allowed only to the single pinned saved generation.');
        }
        if ($requestedCore->canonical !== $saved->coreVersion->canonical) {
            throw new RuntimeException('Requested rollback core version does not match saved generation.');
        }
    }
}

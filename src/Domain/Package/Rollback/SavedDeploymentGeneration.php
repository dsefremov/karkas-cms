<?php

declare(strict_types=1);

namespace Karkas\Domain\Package\Rollback;

use DateTimeImmutable;
use Karkas\Domain\Platform\SemVerVersion;

/**
 * At most one pinned previous generation eligible for controlled rollback (spec §6–§7).
 */
final readonly class SavedDeploymentGeneration
{
    public function __construct(
        public string $generationId,
        public SemVerVersion $coreVersion,
        /** @var array<string, SemVerVersion> module id => version */
        public array $moduleVersions,
        public string $planDigest,
        public ?DateTimeImmutable $savedAt,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Inventory;

use DateTimeImmutable;
use DateTimeInterface;
use Karkas\Domain\Package\VersionAvailabilityStatus;
use Karkas\Domain\Package\VersionSourceKind;
use Karkas\Domain\Platform\SemVerVersion;

final readonly class PackageVersionSlot
{
    public function __construct(
        public string $packageId,
        public ?SemVerVersion $current,
        public ?SemVerVersion $available,
        public ?SemVerVersion $target,
        public VersionSourceKind $sourceKind,
        public VersionAvailabilityStatus $status,
        public ?string $compatibilityReason = null,
        public ?string $metadataDigest = null,
        public ?DateTimeImmutable $fetchedAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'package_id' => $this->packageId,
            'current' => $this->current?->canonical,
            'available' => $this->available?->canonical,
            'target' => $this->target?->canonical,
            'source' => [
                'kind' => $this->sourceKind->value,
                'metadata_digest' => $this->metadataDigest,
                'fetched_at' => $this->fetchedAt?->format(DateTimeInterface::ATOM),
            ],
            'status' => $this->status->value,
            'compatibility_reason' => $this->compatibilityReason,
        ];
    }
}

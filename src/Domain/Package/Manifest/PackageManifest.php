<?php

declare(strict_types=1);

namespace Karkas\Domain\Package\Manifest;

use Karkas\Domain\Module\ModuleId;
use Karkas\Domain\Platform\SemVerVersion;

/**
 * Parsed unsigned package manifest (spec §6 manifest protocol).
 */
final readonly class PackageManifest
{
    /**
     * @param array<string, string>                     $dependencies
     * @param array<string, string>                     $conflicts
     * @param array<string, string>                     $requiredExtensions
     * @param list<array{id: string, checksum: string}> $migrations
     * @param list<ManifestFileEntry>                   $files
     */
    public function __construct(
        public ManifestFormat $format,
        public ModuleId $componentId,
        public string $componentType,
        public SemVerVersion $version,
        public string $requiresCore,
        public string $requiresSdk,
        public string $requiresPhp,
        public ?string $requiresMysqlServer,
        public array $requiredExtensions,
        public array $dependencies,
        public array $conflicts,
        public array $migrations,
        public array $files,
        public ?string $manifestDigest = null,
        public ?string $displayName = null,
    ) {
    }

    public function isCorePackage(): bool
    {
        return ManifestFormat::Core === $this->format;
    }
}

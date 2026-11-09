<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Lifecycle;

use Karkas\Domain\Package\Manifest\PackageManifest;

final readonly class StagedPackage
{
    public function __construct(
        public PackageManifest $manifest,
        public string $manifestDigest,
        public string $stagingRelativePath,
        public string $absolutePath,
    ) {
    }
}

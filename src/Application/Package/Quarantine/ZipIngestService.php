<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Quarantine;

use Karkas\Application\Package\Lifecycle\PackageStagingService;
use Karkas\Application\Package\Lifecycle\StagedPackage;

final class ZipIngestService
{
    public function __construct(
        private readonly string $quarantineRoot,
        private readonly ZipQuarantineExtractor $extractor,
        private readonly PackageStagingService $staging,
    ) {
    }

    public function ingestUploadedZip(string $uploadedZipPath): StagedPackage
    {
        $quarantineDir = $this->quarantineRoot.'/'.bin2hex(random_bytes(16));
        $this->extractor->extractToQuarantine($uploadedZipPath, $quarantineDir);

        return $this->staging->stageFromDirectory($quarantineDir);
    }
}

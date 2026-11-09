<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Package;

use Karkas\Application\Package\Manifest\ManifestJsonParser;
use Karkas\Application\Package\Manifest\ManifestValidationException;
use Karkas\Domain\ModuleSdk\Contract\ManifestInspectionPort;
use Karkas\Domain\Package\Manifest\PackageManifest;

final class ManifestInspectionAdapter implements ManifestInspectionPort
{
    public function __construct(
        private readonly ManifestJsonParser $parser,
    ) {
    }

    public function inspectFromJson(string $json): PackageManifest
    {
        return $this->parser->parse($json);
    }

    public function inspectFromFile(string $absolutePath): PackageManifest
    {
        if (!is_file($absolutePath)) {
            throw new ManifestValidationException(sprintf('Manifest file not found: "%s".', $absolutePath));
        }
        $json = file_get_contents($absolutePath);
        if (false === $json) {
            throw new ManifestValidationException(sprintf('Cannot read manifest: "%s".', $absolutePath));
        }

        return $this->parser->parse($json);
    }
}

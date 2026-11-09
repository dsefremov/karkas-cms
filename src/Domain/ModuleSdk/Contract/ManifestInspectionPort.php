<?php

declare(strict_types=1);

namespace Karkas\Domain\ModuleSdk\Contract;

use Karkas\Domain\Package\Manifest\PackageManifest;

/**
 * Read-only manifest validation boundary: parse/schema/structure without include/autoload (spec §6).
 */
interface ManifestInspectionPort
{
    /**
     * @throws \Karkas\Application\Package\Manifest\ManifestValidationException
     */
    public function inspectFromJson(string $json): PackageManifest;

    /**
     * @throws \Karkas\Application\Package\Manifest\ManifestValidationException
     */
    public function inspectFromFile(string $absolutePath): PackageManifest;
}

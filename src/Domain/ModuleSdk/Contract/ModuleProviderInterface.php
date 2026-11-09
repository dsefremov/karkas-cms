<?php

declare(strict_types=1);

namespace Karkas\Domain\ModuleSdk\Contract;

use Karkas\Domain\Package\Manifest\PackageManifest;

/**
 * Entry point declared in an installed module package (no autoload at inspection time).
 */
interface ModuleProviderInterface
{
    public function manifest(): PackageManifest;

    public function moduleId(): string;
}

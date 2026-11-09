<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Compatibility;

use Karkas\Domain\Platform\SemVerVersion;
use RuntimeException;

final class DowngradeRefusalException extends RuntimeException
{
    public function __construct(
        public readonly string $packageId,
        public readonly SemVerVersion $current,
        public readonly SemVerVersion $requested,
    ) {
        parent::__construct(sprintf(
            'Downgrade refused for "%s": current %s, requested %s.',
            $packageId,
            $current->canonical,
            $requested->canonical,
        ));
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Quarantine;

use RuntimeException;

final class UnsafeArchiveException extends RuntimeException
{
    /**
     * @param list<string> $reasons
     */
    public function __construct(
        public readonly array $reasons,
    ) {
        parent::__construct('Package archive rejected: '.implode('; ', $reasons));
    }
}

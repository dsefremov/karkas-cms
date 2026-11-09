<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Manifest;

use RuntimeException;

final class ManifestValidationException extends RuntimeException
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        string $message,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }
}

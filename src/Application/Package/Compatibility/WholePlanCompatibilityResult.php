<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Compatibility;

final readonly class WholePlanCompatibilityResult
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public bool $compatible,
        public array $errors = [],
    ) {
    }

    public static function ok(): self
    {
        return new self(true);
    }

    /**
     * @param list<string> $errors
     */
    public static function failed(array $errors): self
    {
        return new self(false, $errors);
    }
}

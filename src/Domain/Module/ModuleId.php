<?php

declare(strict_types=1);

namespace Karkas\Domain\Module;

use InvalidArgumentException;

/**
 * Stable vendor/module identifier (e.g. karkas/blog).
 */
final readonly class ModuleId
{
    public function __construct(
        public string $value,
    ) {
        if (!preg_match('/^[a-z][a-z0-9]*\/[a-z][a-z0-9]*(\.[a-z0-9]+)*$/', $this->value)) {
            throw new InvalidArgumentException(sprintf('Invalid module id "%s".', $this->value));
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Domain\Platform;

use InvalidArgumentException;

/**
 * Canonical package SemVer (spec §6): MAJOR.MINOR.PATCH with optional prerelease/build.
 */
final readonly class SemVerVersion
{
    private const CANONICAL_PATTERN = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)'
        .'(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?'
        .'(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    public function __construct(
        public string $canonical,
    ) {
        if (!preg_match(self::CANONICAL_PATTERN, $this->canonical)) {
            throw new InvalidArgumentException(sprintf('Invalid SemVer "%s".', $this->canonical));
        }
    }

    public static function tryParse(string $value): ?self
    {
        try {
            return new self($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function isStable(): bool
    {
        return !str_contains($this->canonical, '-');
    }

    public function __toString(): string
    {
        return $this->canonical;
    }
}

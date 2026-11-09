<?php

declare(strict_types=1);

namespace Karkas\Domain\Package\Manifest;

use InvalidArgumentException;

enum ManifestFormat: string
{
    case Core = 'cms-core/v1';
    case Module = 'cms-module/v1';

    public static function fromString(string $value): self
    {
        return match ($value) {
            self::Core->value => self::Core,
            self::Module->value => self::Module,
            default => throw new InvalidArgumentException(sprintf('Unsupported manifest format "%s".', $value)),
        };
    }
}

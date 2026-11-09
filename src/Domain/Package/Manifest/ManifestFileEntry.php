<?php

declare(strict_types=1);

namespace Karkas\Domain\Package\Manifest;

use InvalidArgumentException;

final readonly class ManifestFileEntry
{
    public function __construct(
        public string $path,
        public int $sizeBytes,
        public string $sha256,
    ) {
        if ($this->sizeBytes < 0) {
            throw new InvalidArgumentException('size_bytes must be non-negative.');
        }
        if (!preg_match('/^[a-f0-9]{64}$/', $this->sha256)) {
            throw new InvalidArgumentException('sha256 must be lowercase hex.');
        }
    }
}

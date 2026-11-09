<?php

declare(strict_types=1);

namespace Karkas\Application\Admin;

use RuntimeException;

final class ZipUploadGate
{
    public function __construct(
        private readonly string $flagPath,
    ) {
    }

    public function isEnabled(): bool
    {
        return is_file($this->flagPath);
    }

    public function assertEnabled(): void
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('ZIP upload is disabled until flag file is present on the server.');
        }
    }
}

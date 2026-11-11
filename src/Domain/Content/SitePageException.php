<?php

declare(strict_types=1);

namespace Karkas\Domain\Content;

use RuntimeException;

final class SitePageException extends RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}

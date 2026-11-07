<?php

declare(strict_types=1);

namespace Karkas\Application\Health;

final readonly class HealthStatus
{
    /**
     * @param array<string, mixed> $checks
     */
    public function __construct(
        public string $status,
        public array $checks = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'checks' => $this->checks,
        ];
    }
}

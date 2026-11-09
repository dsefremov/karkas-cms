<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Plan;

use Karkas\Application\Package\Compatibility\WholePlanCompatibilityResult;

final readonly class PlatformPlanResult
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $planDigest,
        public WholePlanCompatibilityResult $compatibility,
        public array $payload,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema' => 'karkas-platform-plan/v1',
            'plan_digest' => $this->planDigest,
            'compatible' => $this->compatibility->compatible,
            'errors' => $this->compatibility->errors,
            'plan' => $this->payload,
        ];
    }
}

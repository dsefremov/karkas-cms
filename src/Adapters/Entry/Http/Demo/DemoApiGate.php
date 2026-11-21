<?php

declare(strict_types=1);

namespace Karkas\Adapters\Entry\Http\Demo;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Demo seed/reset API is dev/test only and additionally requires KARKAS_ALLOW_DEMO_SEED=1.
 * In any other case the endpoints answer 404 (as if the route did not exist).
 */
final class DemoApiGate
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
        #[Autowire('%env(default::KARKAS_ALLOW_DEMO_SEED)%')]
        private readonly ?string $allowFlag,
    ) {
    }

    public function isEnabled(): bool
    {
        return \in_array($this->environment, ['dev', 'test'], true) && '1' === $this->allowFlag;
    }
}

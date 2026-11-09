<?php

declare(strict_types=1);

namespace Karkas\Domain\ModuleSdk\Contract;

/**
 * Core bridge to versioned module descriptors (spec §5); wiring in later gates.
 */
interface CoreModuleBridgeInterface
{
    /**
     * @return list<string> registered stable module ids
     */
    public function registeredModuleIds(): array;

    public function sdkVersion(): string;
}

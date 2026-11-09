<?php

declare(strict_types=1);

namespace Karkas\Domain\ModuleSdk\Contract;

/**
 * Explicit lifecycle stages after structural validation (spec §7); implementations arrive in G2+.
 */
interface ModuleLifecyclePort
{
    public function prepareInstall(string $operationId): void;

    public function prepareUpdate(string $operationId): void;

    public function prepareDisable(string $operationId): void;

    public function prepareUninstall(string $operationId): void;
}

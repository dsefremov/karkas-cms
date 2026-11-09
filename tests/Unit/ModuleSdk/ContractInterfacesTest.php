<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\ModuleSdk;

use Karkas\Domain\ModuleSdk\Contract\CoreModuleBridgeInterface;
use Karkas\Domain\ModuleSdk\Contract\ManifestInspectionPort;
use Karkas\Domain\ModuleSdk\Contract\ModuleLifecyclePort;
use Karkas\Domain\ModuleSdk\Contract\ModuleProviderInterface;
use Karkas\Domain\ModuleSdk\Contract\WholePlanCompatibilityPort;
use PHPUnit\Framework\TestCase;

final class ContractInterfacesTest extends TestCase
{
    public function testSdkContractsAreInterfaces(): void
    {
        self::assertTrue(interface_exists(ModuleProviderInterface::class));
        self::assertTrue(interface_exists(ModuleLifecyclePort::class));
        self::assertTrue(interface_exists(CoreModuleBridgeInterface::class));
        self::assertTrue(interface_exists(ManifestInspectionPort::class));
        self::assertTrue(interface_exists(WholePlanCompatibilityPort::class));
    }

    public function testAdaptersImplementInspectionAndPlanPorts(): void
    {
        self::assertContains(ManifestInspectionPort::class, class_implements(\Karkas\Adapters\Infrastructure\Package\ManifestInspectionAdapter::class) ?: []);
        self::assertContains(WholePlanCompatibilityPort::class, class_implements(\Karkas\Application\Package\Compatibility\WholePlanCompatibilityChecker::class) ?: []);
    }
}

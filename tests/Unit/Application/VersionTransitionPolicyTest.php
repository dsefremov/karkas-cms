<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\Application;

use Karkas\Application\Package\Compatibility\DowngradeRefusalException;
use Karkas\Application\Package\Compatibility\SemVerConstraintEvaluator;
use Karkas\Application\Package\Compatibility\VersionTransitionPolicy;
use Karkas\Domain\Package\Rollback\SavedDeploymentGeneration;
use Karkas\Domain\Platform\SemVerVersion;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class VersionTransitionPolicyTest extends TestCase
{
    private VersionTransitionPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new VersionTransitionPolicy(new SemVerConstraintEvaluator());
    }

    public function testRefusesDowngrade(): void
    {
        $this->expectException(DowngradeRefusalException::class);
        $this->policy->assertUpgradeOrEqual(
            'karkas/blog',
            new SemVerVersion('2.0.0'),
            new SemVerVersion('1.9.9'),
        );
    }

    public function testAllowsUpgrade(): void
    {
        $this->policy->assertUpgradeOrEqual(
            'karkas/blog',
            new SemVerVersion('1.0.0'),
            new SemVerVersion('1.0.1'),
        );
        $this->addToAssertionCount(1);
    }

    public function testRollbackOnlyToPinnedGeneration(): void
    {
        $saved = new SavedDeploymentGeneration(
            generationId: 'gen-1',
            coreVersion: new SemVerVersion('1.0.0'),
            moduleVersions: [],
            planDigest: 'abc',
            savedAt: null,
        );
        $this->policy->assertControlledRollbackTarget(
            $saved,
            new SemVerVersion('1.0.0'),
            $saved,
        );

        $this->expectException(RuntimeException::class);
        $this->policy->assertControlledRollbackTarget(
            $saved,
            new SemVerVersion('1.0.0'),
            new SavedDeploymentGeneration('gen-2', new SemVerVersion('0.9.0'), [], 'def', null),
        );
    }
}

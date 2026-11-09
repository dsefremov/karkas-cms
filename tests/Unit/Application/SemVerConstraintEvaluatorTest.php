<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\Application;

use Karkas\Application\Package\Compatibility\SemVerConstraintEvaluator;
use Karkas\Application\Package\Compatibility\UnsupportedConstraintException;
use Karkas\Domain\Platform\SemVerVersion;
use PHPUnit\Framework\TestCase;

final class SemVerConstraintEvaluatorTest extends TestCase
{
    private SemVerConstraintEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new SemVerConstraintEvaluator();
    }

    public function testCaretRange(): void
    {
        $v = new SemVerVersion('1.2.4');
        self::assertTrue($this->evaluator->satisfies($v, '^1.2.0'));
        self::assertFalse($this->evaluator->satisfies($v, '^2.0.0'));
    }

    public function testPrereleaseRequiresExactConstraint(): void
    {
        $v = new SemVerVersion('1.0.0-beta.1');
        self::assertTrue($this->evaluator->satisfies($v, '=1.0.0-beta.1'));
        self::assertFalse($this->evaluator->satisfies($v, '^1.0.0'));
    }

    public function testRejectsBareMinorAlias(): void
    {
        $this->expectException(UnsupportedConstraintException::class);
        $this->evaluator->assertSupportedConstraintSyntax('1.2');
    }

    public function testCompareOrdering(): void
    {
        self::assertSame(1, $this->evaluator->compare(new SemVerVersion('2.0.0'), new SemVerVersion('1.9.9')));
        self::assertSame(-1, $this->evaluator->compare(new SemVerVersion('1.0.0'), new SemVerVersion('1.0.1')));
    }
}

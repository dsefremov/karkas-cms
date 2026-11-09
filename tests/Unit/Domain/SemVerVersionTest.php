<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\Domain;

use InvalidArgumentException;
use Karkas\Domain\Platform\SemVerVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SemVerVersionTest extends TestCase
{
    public function testAcceptsCanonicalStable(): void
    {
        $v = new SemVerVersion('1.2.3');
        self::assertTrue($v->isStable());
    }

    public function testRejectsLeadingZeroMajor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SemVerVersion('01.2.3');
    }

    #[DataProvider('validProvider')]
    public function testValidStrings(string $value): void
    {
        self::assertNotNull(SemVerVersion::tryParse($value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validProvider(): iterable
    {
        yield 'patch' => ['0.0.1'];
        yield 'prerelease' => ['1.0.0-alpha.1'];
        yield 'build' => ['1.0.0+build.1'];
    }
}

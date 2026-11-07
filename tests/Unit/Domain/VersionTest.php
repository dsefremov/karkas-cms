<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\Domain;

use Karkas\Domain\Platform\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testStageZero(): void
    {
        self::assertSame('0', Version::STAGE);
        self::assertSame('Karkas CMS', Version::PRODUCT);
    }
}

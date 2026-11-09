<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\Domain;

use Karkas\Domain\Platform\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testStageOne(): void
    {
        self::assertSame('1', Version::STAGE);
        self::assertSame('Karkas CMS', Version::PRODUCT);
    }
}

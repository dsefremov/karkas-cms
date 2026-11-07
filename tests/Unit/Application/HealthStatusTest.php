<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\Application;

use Karkas\Application\Health\HealthStatus;
use PHPUnit\Framework\TestCase;

final class HealthStatusTest extends TestCase
{
    public function testToArray(): void
    {
        $status = new HealthStatus('healthy', ['php' => ['version' => '8.4.0']]);

        self::assertSame([
            'status' => 'healthy',
            'checks' => ['php' => ['version' => '8.4.0']],
        ], $status->toArray());
    }
}

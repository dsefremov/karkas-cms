<?php

declare(strict_types=1);

namespace Karkas\Tests\Integration;

use Karkas\Adapters\Infrastructure\Persistence\MysqlConnectivityProbe;
use PHPUnit\Framework\TestCase;

final class MysqlConnectivityProbeTest extends TestCase
{
    public function testProbeAgainstRealMysql(): void
    {
        $url = getenv('DATABASE_URL') ?: $_ENV['DATABASE_URL'] ?? null;
        if (!is_string($url) || '' === $url) {
            self::markTestSkipped('DATABASE_URL is not set');
        }

        $probe = new MysqlConnectivityProbe();
        $result = $probe->probe($url);

        self::assertTrue($result['connected']);
        self::assertNotEmpty($result['version']);
        self::assertSame('utf8mb4', $result['character_set_database']);
        self::assertGreaterThanOrEqual(1, $result['fulltext_ru']['matches']);
        self::assertSame('Блог', $result['fulltext_ru']['sample']);
    }
}

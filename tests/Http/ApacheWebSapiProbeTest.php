<?php

declare(strict_types=1);

namespace Karkas\Tests\Http;

use PHPUnit\Framework\TestCase;

/**
 * Hits a real Apache/mod_php (or php-fpm) stack — skipped unless STAGE0_HTTP_PROBE_BASE_URL is set (CI Playwright job, make e2e).
 */
final class ApacheWebSapiProbeTest extends TestCase
{
    public function testWebSapiDisablesShellFunctions(): void
    {
        $base = getenv('STAGE0_HTTP_PROBE_BASE_URL') ?: $_ENV['STAGE0_HTTP_PROBE_BASE_URL'] ?? '';
        if ('' === $base) {
            self::markTestSkipped('STAGE0_HTTP_PROBE_BASE_URL is not set (requires Apache Docker stack).');
        }

        $url = rtrim($base, '/').'/_stage0/web-sapi-probe';
        $json = file_get_contents($url);
        self::assertIsString($json);

        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertContains('shell_exec', $data['disable_functions'] ?? []);
        self::assertFalse($data['shell_exec_callable'] ?? true);
        self::assertFalse($data['exec_callable'] ?? true);
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Tests\HostingCheck;

use PHPUnit\Framework\TestCase;

final class HostingCheckJsonTest extends TestCase
{
    public function testHostingCheckProducesValidJson(): void
    {
        $token = bin2hex(random_bytes(32));
        $json = $this->buildReportViaCli($token);
        self::assertArrayHasKey('meta', $json);
        self::assertArrayHasKey('php', $json);
        self::assertArrayHasKey('assessment', $json);
        self::assertSame('1.0.0', $json['meta']['report_version']);
        self::assertArrayHasKey('extensions', $json['php']);
        self::assertTrue($json['php']['extensions']['pdo_mysql'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportViaCli(string $token): array
    {
        $helper = dirname(__DIR__, 2).'/tests/HostingCheck/hosting_check_json_helper.php';
        $cmd = [PHP_BINARY, $helper, $token];
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__, 2));
        self::assertIsResource($proc);
        $json = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($proc));

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Tests\HostingCheck;

use PHPUnit\Framework\TestCase;

final class HostingCheckSecurityTest extends TestCase
{
    public function testRefusesAccessWithoutToken(): void
    {
        $script = dirname(__DIR__, 2).'/tools/hosting-check.php';
        $cmd = [PHP_BINARY, $script];
        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2).'/tools');
        self::assertIsResource($proc);
        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);

        self::assertStringContainsString('Доступ запрещён', $out);
    }

    public function testEmitJsonRefusesWithoutToken(): void
    {
        $script = dirname(__DIR__, 2).'/tools/hosting-check.php';
        $cmd = [PHP_BINARY, $script, '--emit-json', 'short'];
        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2).'/tools');
        self::assertIsResource($proc);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        self::assertSame(2, $code);
        self::assertStringContainsString('unauthorized', $stderr);
    }

    public function testOptionalDbReportNeverEchoesPassword(): void
    {
        $script = dirname(__DIR__, 2).'/tools/hosting-check.php';
        $token = bin2hex(random_bytes(32));
        $password = 'must-not-appear-in-output-'.bin2hex(random_bytes(8));

        $proc = proc_open(
            [PHP_BINARY, $script, '--emit-json', $token],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            dirname(__DIR__, 2).'/tools',
            null,
            ['bypass_shell' => true],
        );
        self::assertIsResource($proc);

        // Simulate POST DB check by running a small driver script (hosting-check reads $_POST only in web SAPI).
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);

        $driverPath = dirname(__DIR__, 2).'/tests/HostingCheck/hosting_check_post_driver.php';
        $cmd = [PHP_BINARY, $driverPath, $token, $password, $script];
        $proc2 = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes2, dirname(__DIR__, 2).'/tools');
        self::assertIsResource($proc2);
        $html = stream_get_contents($pipes2[1]);
        fclose($pipes2[1]);
        $err = stream_get_contents($pipes2[2]);
        fclose($pipes2[2]);
        $code = proc_close($proc2);

        self::assertSame(0, $code, $err);
        self::assertStringNotContainsString($password, $html);
    }
}

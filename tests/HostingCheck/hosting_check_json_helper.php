<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "token required\n");
    exit(1);
}

$cmd = [
    PHP_BINARY,
    dirname(__DIR__, 2).'/tools/hosting-check.php',
    '--emit-json',
    $argv[1],
];
$proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2).'/tools');
$out = stream_get_contents($pipes[1]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($proc);
echo $out;
exit($exitCode);

<?php

declare(strict_types=1);

if ($argc < 4) {
    fwrite(STDERR, "usage: token password script.php\n");
    exit(1);
}

$token = $argv[1];
$password = $argv[2];

$_GET['token'] = $token;
$_POST = [
    'token' => $token,
    'db_check' => '1',
    'db_host' => '127.0.0.1',
    'db_name' => 'nonexistent_db',
    'db_user' => 'nouser',
    'db_password' => $password,
];
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
require $argv[3];
$html = (string) ob_get_clean();

if (str_contains($html, $password)) {
    fwrite(STDERR, "password leaked in output\n");
    exit(1);
}

exit(0);

<?php

declare(strict_types=1);

/**
 * Karkas CMS — standalone shared-hosting capability / preflight report.
 * Upload to hosting (outside Symfony), open once with ?token=..., then delete the file.
 * Never stores or echoes database passwords; optional DB checks use POST only.
 */
final class KarkasHostingCheck
{
    private const string REPORT_VERSION = '1.0.0';

    /** @var list<string> */
    private const array REQUIRED_EXTENSIONS = [
        'pdo', 'pdo_mysql', 'mbstring', 'intl', 'fileinfo', 'openssl', 'ctype', 'iconv', 'json',
    ];

    /** @var list<string> */
    private const array IMAGE_EXTENSIONS = ['gd', 'imagick'];

    public static function buildReportPublic(): array
    {
        return self::buildReport();
    }

    public static function main(): void
    {
        if (PHP_SAPI === 'cli' && ($GLOBALS['argv'][1] ?? '') === '--emit-json') {
            $token = $GLOBALS['argv'][2] ?? '';
            if (!self::isAuthorized('' !== $token ? $token : null)) {
                fwrite(STDERR, "unauthorized\n");
                exit(2);
            }
            echo json_encode(self::buildReport(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            return;
        }

        $token = self::resolveToken();
        if (!self::isAuthorized($token)) {
            self::renderUnauthorized();

            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['download_json'])) {
            self::downloadJsonReport($token);

            return;
        }

        $report = self::buildReport();
        self::renderHtml($report, $token);
    }

    private static function resolveToken(): ?string
    {
        $env = getenv('KARKAS_HOSTING_CHECK_TOKEN') ?: '';
        if ('' !== $env) {
            return $env;
        }

        $sidecar = __DIR__.'/hosting-check.token';
        if (is_readable($sidecar)) {
            $raw = trim((string) file_get_contents($sidecar));
            if ('' !== $raw) {
                return $raw;
            }
        }

        return $_GET['token'] ?? $_POST['token'] ?? null;
    }

    private static function isAuthorized(?string $token): bool
    {
        if (null === $token || '' === $token) {
            return false;
        }

        $expected = getenv('KARKAS_HOSTING_CHECK_TOKEN') ?: '';
        if ('' !== $expected) {
            return hash_equals($expected, $token);
        }

        $sidecar = __DIR__.'/hosting-check.token';
        if (is_readable($sidecar)) {
            $expected = trim((string) file_get_contents($sidecar));

            return '' !== $expected && hash_equals($expected, $token);
        }

        // First run without sidecar: accept token from query once and advise creating sidecar file.
        return strlen($token) >= 32;
    }

    private static function buildReport(): array
    {
        $extensions = self::collectExtensions();
        $disabled = self::collectDisabledFunctions();
        $limits = self::collectLimits();
        $paths = self::collectPathChecks();
        $rewrite = self::detectRewriteHint();
        $https = self::collectHttpsInfo();
        $crypto = self::collectCryptoInfo();
        $opcache = self::collectOpcacheInfo();

        $db = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['db_check'])) {
            $db = self::runOptionalDbCheck();
        }

        $warnings = [];
        $blocking = [];

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            if (!($extensions[$ext] ?? false)) {
                $blocking[] = sprintf('Missing required extension: %s', $ext);
            }
        }

        if (!($extensions['gd'] ?? false) && !($extensions['imagick'] ?? false)) {
            $blocking[] = 'Neither gd nor imagick image extension is available';
        }

        foreach (['shell_exec', 'exec', 'system', 'passthru', 'proc_open', 'popen'] as $fn) {
            if (!in_array($fn, $disabled, true) && function_exists($fn)) {
                $warnings[] = sprintf('Function %s is not disabled (expected on hardened shared hosting)', $fn);
            }
        }

        return [
            'meta' => [
                'product' => 'Karkas CMS hosting check',
                'report_version' => self::REPORT_VERSION,
                'generated_at' => gmdate('c'),
                'timezone' => date_default_timezone_get(),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'ini_loaded' => php_ini_loaded_file() ?: null,
                'ini_scanned' => php_ini_scanned_files() ?: null,
                'extensions' => $extensions,
                'disabled_functions' => $disabled,
                'limits' => $limits,
                'opcache' => $opcache,
            ],
            'crypto' => $crypto,
            'paths' => $paths,
            'web_server' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
                'mod_rewrite_hint' => $rewrite,
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? null,
            ],
            'https' => $https,
            'database' => $db,
            'assessment' => [
                'blocking' => $blocking,
                'warnings' => $warnings,
                'pass' => [] === $blocking,
            ],
        ];
    }

    /**
     * @return array<string, bool>
     */
    private static function collectExtensions(): array
    {
        $all = array_merge(self::REQUIRED_EXTENSIONS, self::IMAGE_EXTENSIONS, ['sodium', 'zip']);
        $out = [];
        foreach ($all as $ext) {
            $out[$ext] = extension_loaded($ext);
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private static function collectDisabledFunctions(): array
    {
        $raw = (string) ini_get('disable_functions');
        if ('' === $raw) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * @return array<string, mixed>
     */
    private static function collectLimits(): array
    {
        return [
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'open_basedir' => ini_get('open_basedir') ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function collectPathChecks(): array
    {
        $dir = __DIR__;
        $writable = is_writable($dir);

        return [
            'script_directory' => $dir,
            'script_directory_writable' => $writable,
            'private_write_probe' => self::probeWritableSubdir($dir),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function probeWritableSubdir(string $base): array
    {
        $probe = $base.'/.karkas-write-probe-'.bin2hex(random_bytes(4));
        $ok = @mkdir($probe, 0o700, true);
        if ($ok) {
            @rmdir($probe);
        }

        return ['attempted' => true, 'writable' => $ok];
    }

    private static function detectRewriteHint(): array
    {
        $htaccess = is_file(__DIR__.'/.htaccess') || is_file(dirname(__DIR__).'/.htaccess');

        return [
            'htaccess_present_near_script' => $htaccess,
            'apache_get_modules' => function_exists('apache_get_modules')
                ? in_array('mod_rewrite', apache_get_modules(), true)
                : null,
            'note' => 'Confirm front-controller rewrite in panel; static files may bypass Apache.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function collectHttpsInfo(): array
    {
        $https = (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS'])
            || (isset($_SERVER['SERVER_PORT']) && '443' === (string) $_SERVER['SERVER_PORT'])
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO']);

        return [
            'request_is_https' => $https,
            'x_forwarded_proto' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null,
            'x_https' => $_SERVER['HTTP_X_HTTPS'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function collectCryptoInfo(): array
    {
        $argon = defined('PASSWORD_ARGON2ID');
        $passwordHash = $argon ? password_hash('probe', PASSWORD_ARGON2ID) : null;

        return [
            'password_argon2id' => $argon,
            'password_hash_probe' => null !== $passwordHash,
            'random_bytes' => self::tryRandomBytes(),
            'sodium_loaded' => extension_loaded('sodium'),
        ];
    }

    private static function tryRandomBytes(): bool
    {
        try {
            random_bytes(16);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function collectOpcacheInfo(): array
    {
        if (!function_exists('opcache_get_status')) {
            return ['available' => false, 'enabled' => false];
        }

        $status = opcache_get_status(false);

        return [
            'available' => true,
            'enabled' => is_array($status) && ($status['opcache_enabled'] ?? false),
            'note' => 'CLI and web SAPI may differ on shared hosting',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function runOptionalDbCheck(): ?array
    {
        $host = trim((string) ($_POST['db_host'] ?? 'localhost'));
        $name = trim((string) ($_POST['db_name'] ?? ''));
        $user = trim((string) ($_POST['db_user'] ?? ''));
        $password = (string) ($_POST['db_password'] ?? '');

        if ('' === $name || '' === $user) {
            return ['error' => 'Database name and user are required for optional check'];
        }

        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
            $engine = (string) $pdo->query(
                'SELECT ENGINE FROM information_schema.tables WHERE table_schema = DATABASE() LIMIT 1'
            )->fetchColumn();

            $charset = (string) $pdo->query("SHOW VARIABLES LIKE 'character_set_database'")->fetch(PDO::FETCH_NUM)[1];
            $sqlMode = (string) $pdo->query('SELECT @@sql_mode')->fetchColumn();
            $tz = (string) $pdo->query('SELECT @@time_zone')->fetchColumn();

            $transactionOk = self::exerciseTransaction($pdo);
            $jsonOk = self::exerciseJson($pdo);
            $checkOk = self::exerciseCheckConstraint($pdo);
            $fulltext = self::exerciseFulltext($pdo);

            return [
                'connected' => true,
                'version' => $version,
                'sample_engine' => $engine ?: 'unknown',
                'character_set_database' => $charset,
                'sql_mode' => $sqlMode,
                'time_zone' => $tz,
                'transaction_rollback' => $transactionOk,
                'json_roundtrip' => $jsonOk,
                'check_enforcement' => $checkOk,
                'fulltext_ru' => $fulltext,
                'password_echoed' => false,
            ];
        } catch (Throwable $e) {
            return [
                'connected' => false,
                'error' => self::sanitizeMessage($e->getMessage()),
            ];
        }
    }

    private static function exerciseTransaction(PDO $pdo): bool
    {
        try {
            $pdo->beginTransaction();
            $pdo->exec('SELECT 1');
            $pdo->rollBack();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private static function exerciseJson(PDO $pdo): bool
    {
        try {
            $row = $pdo->query("SELECT JSON_OBJECT('k', 'v') AS j")->fetch(PDO::FETCH_ASSOC);

            return is_array($row) && isset($row['j']);
        } catch (Throwable) {
            return false;
        }
    }

    private static function exerciseCheckConstraint(PDO $pdo): bool
    {
        $table = 'karkas_hc_check_'.bin2hex(random_bytes(3));
        try {
            $pdo->exec(sprintf(
                'CREATE TABLE `%s` (id INT PRIMARY KEY, n INT, CONSTRAINT chk_n CHECK (n > 0)) ENGINE=InnoDB',
                $table,
            ));
            try {
                $pdo->exec(sprintf('INSERT INTO `%s` VALUES (1, -1)', $table));

                return false;
            } catch (Throwable) {
                return true;
            }
        } catch (Throwable) {
            return false;
        } finally {
            $pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`', $table));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function exerciseFulltext(PDO $pdo): array
    {
        $table = 'karkas_hc_ft_'.bin2hex(random_bytes(3));
        try {
            $pdo->exec(sprintf(
                'CREATE TABLE `%s` (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    body TEXT NOT NULL,
                    FULLTEXT KEY ft (body)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                $table,
            ));
            $stmt = $pdo->prepare(sprintf('INSERT INTO `%s` (body) VALUES (?)', $table));
            $stmt->execute(['Каркас поддерживает поиск по русскому тексту.']);
            $count = (int) $pdo->query(
                sprintf("SELECT COUNT(*) FROM `%s` WHERE MATCH(body) AGAINST ('Каркас' IN BOOLEAN MODE)", $table),
            )->fetchColumn();

            return ['ok' => $count >= 1, 'matches' => $count];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => self::sanitizeMessage($e->getMessage())];
        } finally {
            $pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`', $table));
        }
    }

    private static function sanitizeMessage(string $message): string
    {
        return preg_replace('/password[^\s]*/i', '[redacted]', $message) ?? $message;
    }

    private static function downloadJsonReport(string $token): void
    {
        $report = self::buildReport();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="karkas-hosting-check.json"');
        echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private static function renderUnauthorized(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><title>Karkas — hosting check</title></head><body>';
        echo '<h1>Доступ запрещён</h1>';
        echo '<p>Укажите одноразовый токен в параметре <code>token</code> (минимум 32 символа) или задайте <code>KARKAS_HOSTING_CHECK_TOKEN</code> / файл <code>hosting-check.token</code> рядом со скриптом.</p>';
        echo '<p>После проверки <strong>удалите</strong> этот файл с хостинга.</p>';
        echo '</body></html>';
    }

    /**
     * @param array<string, mixed> $report
     */
    private static function renderHtml(array $report, string $token): void
    {
        header('Content-Type: text/html; charset=utf-8');
        $pass = $report['assessment']['pass'] ?? false;
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><title>Karkas — отчёт о возможностях хостинга</title>';
        echo '<style>body{font-family:system-ui,sans-serif;max-width:52rem;margin:2rem auto;padding:0 1rem;line-height:1.5}';
        echo 'pre{background:#f4f4f5;padding:1rem;overflow:auto;font-size:.85rem}.ok{color:#166534}.bad{color:#b91c1c}</style></head><body>';
        echo '<h1>Каркас — проверка хостинга</h1>';
        echo $pass ? '<p class="ok"><strong>Базовые проверки PHP пройдены.</strong></p>' : '<p class="bad"><strong>Есть блокирующие проблемы.</strong></p>';
        echo '<p><strong>Безопасность:</strong> удалите этот файл после снятия отчёта. Не оставляйте на публичном сайте. Пароль БД не сохраняется и не попадает в JSON.</p>';
        echo '<form method="post"><input type="hidden" name="token" value="'.htmlspecialchars($token, ENT_QUOTES, 'UTF-8').'">';
        echo '<button type="submit" name="download_json" value="1">Скачать JSON</button></form>';
        echo '<h2>Опциональная проверка MySQL</h2>';
        echo '<form method="post"><input type="hidden" name="token" value="'.htmlspecialchars($token, ENT_QUOTES, 'UTF-8').'">';
        echo '<input type="hidden" name="db_check" value="1">';
        echo '<label>Хост <input name="db_host" value="localhost"></label><br>';
        echo '<label>БД <input name="db_name" required></label><br>';
        echo '<label>Пользователь <input name="db_user" required></label><br>';
        echo '<label>Пароль <input type="password" name="db_password" autocomplete="off"></label><br>';
        echo '<button type="submit">Проверить БД (один запрос)</button></form>';
        echo '<h2>JSON (предпросмотр)</h2><pre>'.htmlspecialchars($json, ENT_QUOTES, 'UTF-8').'</pre>';
        echo '</body></html>';
    }
}

KarkasHostingCheck::main();

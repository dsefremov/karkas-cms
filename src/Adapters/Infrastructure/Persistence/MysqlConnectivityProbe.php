<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use InvalidArgumentException;
use PDO;

/**
 * Shared-hosting-friendly DB smoke checks (InnoDB, utf8mb4, FULLTEXT on Russian text).
 */
final class MysqlConnectivityProbe
{
    /**
     * @return array<string, mixed>
     */
    public function probe(string $databaseUrl): array
    {
        $pdo = $this->connect($databaseUrl);

        $versionStmt = $pdo->query('SELECT VERSION()');
        if ($versionStmt === false) {
            throw new \RuntimeException('Failed to query MySQL version');
        }
        $version = (string) $versionStmt->fetchColumn();

        $charsetStmt = $pdo->query("SHOW VARIABLES LIKE 'character_set_database'");
        if ($charsetStmt === false) {
            throw new \RuntimeException('Failed to query character_set_database');
        }
        $charsetRow = $charsetStmt->fetch(PDO::FETCH_ASSOC);

        $collationStmt = $pdo->query("SHOW VARIABLES LIKE 'collation_database'");
        if ($collationStmt === false) {
            throw new \RuntimeException('Failed to query collation_database');
        }
        $collationRow = $collationStmt->fetch(PDO::FETCH_ASSOC);

        $fulltext = $this->exerciseFulltextRussian($pdo);

        return [
            'connected' => true,
            'version' => $version,
            'character_set_database' => $charsetRow['Value'] ?? null,
            'collation_database' => $collationRow['Value'] ?? null,
            'fulltext_ru' => $fulltext,
        ];
    }

    private function connect(string $databaseUrl): PDO
    {
        $params = $this->parseDatabaseUrl($databaseUrl);

        return new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $params['host'], $params['port'], $params['dbname']),
            $params['user'],
            $params['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
        );
    }

    /**
     * @return array{host: string, port: int, dbname: string, user: string, password: string}
     */
    private function parseDatabaseUrl(string $url): array
    {
        $parts = parse_url($url);
        if (false === $parts || ($parts['scheme'] ?? '') !== 'mysql') {
            throw new InvalidArgumentException('DATABASE_URL must be a mysql:// URL');
        }

        return [
            'host' => $parts['host'] ?? 'localhost',
            'port' => isset($parts['port']) ? (int) $parts['port'] : 3306,
            'dbname' => ltrim($parts['path'] ?? '', '/'),
            'user' => $parts['user'] ?? '',
            'password' => $parts['pass'] ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function exerciseFulltextRussian(PDO $pdo): array
    {
        $table = 'karkas_stage0_fulltext_smoke';

        $pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`', $table));
        $pdo->exec(sprintf(
            'CREATE TABLE `%s` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                FULLTEXT KEY ft_body (body)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            $table,
        ));

        $stmt = $pdo->prepare(sprintf('INSERT INTO `%s` (title, body) VALUES (?, ?)', $table));
        $stmt->execute(['Блог', 'Каркас — система управления контентом для блога на общем хостинге.']);

        $search = $pdo->prepare(sprintf(
            'SELECT id, title FROM `%s` WHERE MATCH(body) AGAINST (? IN BOOLEAN MODE)',
            $table,
        ));
        $search->execute(['Каркас*']);
        $rows = $search->fetchAll();

        $pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`', $table));

        return [
            'matches' => count($rows),
            'sample' => $rows[0]['title'] ?? null,
        ];
    }
}

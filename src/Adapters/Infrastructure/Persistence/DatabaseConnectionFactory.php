<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use RuntimeException;

final class DatabaseConnectionFactory
{
    public function __construct(
        private readonly ?string $databaseUrl,
    ) {
    }

    public function isConfigured(): bool
    {
        return null !== $this->databaseUrl && '' !== $this->databaseUrl;
    }

    public function create(): Connection
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('DATABASE_URL is not configured.');
        }

        $databaseUrl = $this->databaseUrl ?? '';

        $parser = new DsnParser(['mysql' => 'pdo_mysql', 'mysqli' => 'pdo_mysql']);
        $params = $parser->parse($databaseUrl);

        return DriverManager::getConnection($params);
    }
}

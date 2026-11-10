<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTime;
use Doctrine\DBAL\Types\Types;

final class PlatformSiteRepository
{
    public const DEFAULT_SITE_ID = 1;

    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    public function ensureDefaultSite(string $label): void
    {
        $connection = $this->connections->create();
        $now = new DateTime();
        $connection->executeStatement(
            'INSERT INTO platform_site (id, label, updated_at) VALUES (1, :label, :now)
             ON DUPLICATE KEY UPDATE label = VALUES(label), updated_at = VALUES(updated_at)',
            ['label' => $label, 'now' => $now],
            ['now' => Types::DATETIME_MUTABLE],
        );
    }

    /**
     * @return array{id: int, label: string}|null
     */
    public function getDefault(): ?array
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative('SELECT id, label FROM platform_site WHERE id = 1');
        if (false === $row) {
            return null;
        }

        return ['id' => (int) $row['id'], 'label' => (string) $row['label']];
    }
}

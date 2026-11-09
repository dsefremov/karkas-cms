<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTime;
use Doctrine\DBAL\Types\Types;

final class MaintenanceModeRepository
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    public function isEnabled(): bool
    {
        $connection = $this->connections->create();
        $enabled = $connection->fetchOne('SELECT enabled FROM platform_maintenance WHERE singleton_id = 1');

        return 1 === (int) $enabled;
    }

    public function setEnabled(bool $enabled, ?string $reason = null): void
    {
        $connection = $this->connections->create();
        $connection->executeStatement(
            'INSERT INTO platform_maintenance (singleton_id, enabled, reason, updated_at) VALUES (1, :enabled, :reason, :now)
             ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), reason = VALUES(reason), updated_at = VALUES(updated_at)',
            [
                'enabled' => $enabled ? 1 : 0,
                'reason' => $reason,
                'now' => new DateTime(),
            ],
            ['now' => Types::DATETIME_MUTABLE],
        );
    }
}

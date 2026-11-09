<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTime;
use Doctrine\DBAL\Types\Types;

final class InstallStateRepository
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    public function status(): string
    {
        $connection = $this->connections->create();
        $status = $connection->fetchOne('SELECT status FROM platform_install_state WHERE singleton_id = 1');

        return false !== $status ? (string) $status : 'pending';
    }

    public function markCompleted(): void
    {
        $connection = $this->connections->create();
        $now = new DateTime();
        $connection->executeStatement(
            'UPDATE platform_install_state SET status = :status, current_step = :step, updated_at = :now WHERE singleton_id = 1',
            ['status' => 'completed', 'step' => 'finalize', 'now' => $now],
            ['now' => Types::DATETIME_MUTABLE],
        );
    }

    public function setStep(string $step): void
    {
        $connection = $this->connections->create();
        $connection->executeStatement(
            'UPDATE platform_install_state SET current_step = :step, updated_at = :now WHERE singleton_id = 1',
            ['step' => $step, 'now' => new DateTime()],
            ['now' => Types::DATETIME_MUTABLE],
        );
    }
}

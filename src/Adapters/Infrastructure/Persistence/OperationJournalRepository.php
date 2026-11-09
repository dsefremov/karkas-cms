<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTime;
use Doctrine\DBAL\Types\Types;

final class OperationJournalRepository
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(
        string $id,
        string $type,
        string $status,
        ?string $phase,
        ?string $actor,
        ?string $planDigest,
        array $payload,
    ): void {
        $now = new DateTime();
        $connection = $this->connections->create();
        $connection->insert('platform_operation', [
            'id' => $id,
            'operation_type' => $type,
            'status' => $status,
            'phase' => $phase,
            'actor_label' => $actor,
            'plan_digest' => $planDigest,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'error_message' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            'created_at' => Types::DATETIME_MUTABLE,
            'updated_at' => Types::DATETIME_MUTABLE,
        ]);
    }

    public function updateStatus(string $id, string $status, ?string $phase, ?string $error = null): void
    {
        $connection = $this->connections->create();
        $connection->update('platform_operation', [
            'status' => $status,
            'phase' => $phase,
            'error_message' => $error,
            'updated_at' => new DateTime(),
        ], ['id' => $id], [
            'updated_at' => Types::DATETIME_MUTABLE,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRecent(int $limit = 20): array
    {
        $connection = $this->connections->create();
        $rows = $connection->fetchAllAssociative(
            'SELECT id, operation_type, status, phase, plan_digest, payload, error_message, created_at, updated_at FROM platform_operation ORDER BY created_at DESC LIMIT :limit',
            ['limit' => $limit],
            ['limit' => Types::INTEGER],
        );

        return array_map(static function (array $row): array {
            $row['payload'] = json_decode((string) $row['payload'], true, 512, JSON_THROW_ON_ERROR);

            return $row;
        }, $rows);
    }
}

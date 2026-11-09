<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Karkas\Domain\Package\Rollback\SavedDeploymentGeneration;
use Karkas\Domain\Platform\SemVerVersion;

final class RollbackSlotRepository
{
    private const SLOT_KEY = 'primary';

    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    public function saveSlot(SavedDeploymentGeneration $generation): void
    {
        $connection = $this->connections->create();
        $moduleVersions = [];
        foreach ($generation->moduleVersions as $id => $version) {
            $moduleVersions[$id] = $version->canonical;
        }
        $now = new DateTime();
        $connection->executeStatement(
            'DELETE FROM deployment_rollback_slot WHERE slot_key = :key',
            ['key' => self::SLOT_KEY],
        );
        $connection->insert('deployment_rollback_slot', [
            'slot_key' => self::SLOT_KEY,
            'generation_id' => $generation->generationId,
            'core_version' => $generation->coreVersion->canonical,
            'plan_digest' => $generation->planDigest,
            'module_versions' => json_encode($moduleVersions, JSON_THROW_ON_ERROR),
            'saved_at' => $now,
        ], ['saved_at' => Types::DATETIME_MUTABLE]);
    }

    public function getSlot(): ?SavedDeploymentGeneration
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative(
            'SELECT generation_id, core_version, plan_digest, module_versions, saved_at FROM deployment_rollback_slot WHERE slot_key = :key',
            ['key' => self::SLOT_KEY],
        );
        if (false === $row) {
            return null;
        }
        /** @var array<string, string> $modules */
        $modules = json_decode((string) $row['module_versions'], true, 512, JSON_THROW_ON_ERROR);
        $parsed = [];
        foreach ($modules as $id => $ver) {
            $parsed[$id] = new SemVerVersion($ver);
        }

        return new SavedDeploymentGeneration(
            generationId: (string) $row['generation_id'],
            coreVersion: new SemVerVersion((string) $row['core_version']),
            moduleVersions: $parsed,
            planDigest: (string) $row['plan_digest'],
            savedAt: new DateTimeImmutable((string) $row['saved_at']),
        );
    }
}

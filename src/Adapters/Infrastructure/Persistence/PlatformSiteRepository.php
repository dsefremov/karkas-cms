<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use DateTime;
use Doctrine\DBAL\Types\Types;
use RuntimeException;
use Throwable;

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
            'INSERT INTO platform_site (id, site_key, label, is_default, canonical_host, updated_at)
             VALUES (1, :key, :label, 1, NULL, :now)
             ON DUPLICATE KEY UPDATE label = VALUES(label), updated_at = VALUES(updated_at)',
            ['key' => 'default', 'label' => $label, 'now' => $now],
            ['now' => Types::DATETIME_MUTABLE],
        );
        if (!$this->hasDefaultSite()) {
            $connection->executeStatement(
                'UPDATE platform_site SET is_default = 1, updated_at = :now WHERE id = 1',
                ['now' => $now],
                ['now' => Types::DATETIME_MUTABLE],
            );
        }
    }

    /**
     * @return array{id: int, site_key: string, label: string, is_default: bool, canonical_host: string|null}|null
     */
    public function getDefault(): ?array
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative(
            'SELECT id, site_key, label, is_default, canonical_host FROM platform_site WHERE is_default = 1 ORDER BY id ASC LIMIT 1',
        );
        if (false === $row) {
            $row = $connection->fetchAssociative(
                'SELECT id, site_key, label, is_default, canonical_host FROM platform_site WHERE id = 1',
            );
        }
        if (false === $row) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function updateLabel(int $id, string $label): void
    {
        $connection = $this->connections->create();
        $connection->update(
            'platform_site',
            ['label' => $label, 'updated_at' => new DateTime()],
            ['id' => $id],
            ['updated_at' => Types::DATETIME_MUTABLE],
        );
    }

    /**
     * @return array{id: int, site_key: string, label: string, is_default: bool, canonical_host: string|null}|null
     */
    public function findById(int $id): ?array
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative(
            'SELECT id, site_key, label, is_default, canonical_host FROM platform_site WHERE id = :id',
            ['id' => $id],
        );

        return false === $row ? null : $this->mapRow($row);
    }

    /**
     * @return array{id: int, site_key: string, label: string, is_default: bool, canonical_host: string|null}|null
     */
    public function findByCanonicalHost(string $host): ?array
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative(
            'SELECT id, site_key, label, is_default, canonical_host FROM platform_site WHERE canonical_host = :host',
            ['host' => $host],
        );

        return false === $row ? null : $this->mapRow($row);
    }

    /**
     * @return array{id: int, site_key: string, label: string, is_default: bool, canonical_host: string|null}|null
     */
    public function findBySiteKey(string $siteKey): ?array
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative(
            'SELECT id, site_key, label, is_default, canonical_host FROM platform_site WHERE site_key = :key',
            ['key' => $siteKey],
        );

        return false === $row ? null : $this->mapRow($row);
    }

    /**
     * @return list<array{id: int, site_key: string, label: string, is_default: bool, canonical_host: string|null}>
     */
    public function listAll(): array
    {
        $connection = $this->connections->create();
        $rows = $connection->fetchAllAssociative(
            'SELECT id, site_key, label, is_default, canonical_host FROM platform_site ORDER BY id ASC',
        );
        $sites = [];
        foreach ($rows as $row) {
            $sites[] = $this->mapRow($row);
        }

        return $sites;
    }

    /**
     * @return array{id: int, site_key: string, label: string, is_default: bool, canonical_host: string|null}
     */
    public function create(string $siteKey, string $label, ?string $canonicalHost, bool $isDefault = false): array
    {
        if ('' === trim($siteKey) || !preg_match('/^[a-z0-9][a-z0-9\-]{0,62}$/', $siteKey)) {
            throw new RuntimeException('Invalid site_key.');
        }
        if (null !== $canonicalHost && '' === trim($canonicalHost)) {
            $canonicalHost = null;
        }
        if ($isDefault && $this->hasDefaultSite()) {
            throw new RuntimeException('default_site_exists');
        }
        $connection = $this->connections->create();
        $now = new DateTime();
        try {
            $connection->insert('platform_site', [
                'site_key' => $siteKey,
                'label' => $label,
                'is_default' => $isDefault ? 1 : 0,
                'canonical_host' => $canonicalHost,
                'updated_at' => $now,
            ], ['updated_at' => Types::DATETIME_MUTABLE]);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            if ($isDefault) {
                throw new RuntimeException('default_site_exists', 0, $e);
            }
            throw $e;
        }
        $id = (int) $connection->lastInsertId();
        $created = $this->findById($id);
        if (null === $created) {
            throw new RuntimeException('Failed to create site.');
        }

        return $created;
    }

    public function hasDefaultSite(): bool
    {
        $connection = $this->connections->create();
        $count = $connection->fetchOne('SELECT COUNT(*) FROM platform_site WHERE is_default = 1');

        return (int) $count > 0;
    }

    public function transferDefault(int $siteId): void
    {
        if (null === $this->findById($siteId)) {
            throw new RuntimeException('site_not_found');
        }
        $connection = $this->connections->create();
        $connection->beginTransaction();
        try {
            $connection->executeStatement('UPDATE platform_site SET is_default = 0 WHERE is_default = 1');
            $updated = $connection->executeStatement(
                'UPDATE platform_site SET is_default = 1, updated_at = :now WHERE id = :id',
                ['id' => $siteId, 'now' => new DateTime()],
                ['now' => Types::DATETIME_MUTABLE],
            );
            if (0 === $updated) {
                throw new RuntimeException('site_not_found');
            }
            $connection->commit();
        } catch (Throwable $e) {
            $connection->rollBack();
            throw $e instanceof RuntimeException ? $e : new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    public function delete(int $siteId): void
    {
        $site = $this->findById($siteId);
        if (null === $site) {
            throw new RuntimeException('site_not_found');
        }
        if ($site['is_default']) {
            throw new RuntimeException('default_site_delete_forbidden');
        }
        $connection = $this->connections->create();
        $connection->delete('platform_site', ['id' => $siteId]);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{id: int, site_key: string, label: string, is_default: bool, canonical_host: string|null}
     */
    private function mapRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'site_key' => (string) $row['site_key'],
            'label' => (string) $row['label'],
            'is_default' => (bool) $row['is_default'],
            'canonical_host' => array_key_exists('canonical_host', $row) && is_string($row['canonical_host']) && '' !== $row['canonical_host']
                ? $row['canonical_host']
                : null,
        ];
    }
}

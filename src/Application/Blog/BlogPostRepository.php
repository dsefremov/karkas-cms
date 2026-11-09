<?php

declare(strict_types=1);

namespace Karkas\Application\Blog;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;

final class BlogPostRepository
{
    public function __construct(
        private readonly DatabaseConnectionFactory $connections,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPublic(int $siteId, string $instanceId): array
    {
        $connection = $this->connections->create();
        $rows = $connection->fetchAllAssociative(
            'SELECT id, slug, title, activity, updated_at FROM blog_post
             WHERE site_id = :site AND module_instance_id = :instance AND activity = :active
             ORDER BY updated_at DESC',
            ['site' => $siteId, 'instance' => $instanceId, 'active' => 'active'],
        );

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $postId): ?array
    {
        $connection = $this->connections->create();
        $row = $connection->fetchAssociative('SELECT * FROM blog_post WHERE id = :id', ['id' => $postId]);

        return false === $row ? null : $row;
    }

    public function insert(
        string $id,
        int $siteId,
        string $instanceId,
        string $slug,
        string $title,
        string $bodyText,
        string $activity,
        string $contentEtag,
    ): void {
        $connection = $this->connections->create();
        $now = new DateTime();
        $connection->insert('blog_post', [
            'id' => $id,
            'site_id' => $siteId,
            'module_instance_id' => $instanceId,
            'slug' => $slug,
            'title' => $title,
            'body_text' => $bodyText,
            'activity' => $activity,
            'content_etag' => $contentEtag,
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            'created_at' => Types::DATETIME_MUTABLE,
            'updated_at' => Types::DATETIME_MUTABLE,
        ]);
    }

    public function update(
        string $id,
        string $slug,
        string $title,
        string $bodyText,
        string $activity,
        string $contentEtag,
    ): void {
        $connection = $this->connections->create();
        $connection->update('blog_post', [
            'slug' => $slug,
            'title' => $title,
            'body_text' => $bodyText,
            'activity' => $activity,
            'content_etag' => $contentEtag,
            'updated_at' => new DateTime(),
        ], ['id' => $id], ['updated_at' => Types::DATETIME_MUTABLE]);
    }

    public function appendVersion(string $postId, int $seq, string $snapshotJson): void
    {
        $connection = $this->connections->create();
        $connection->insert('blog_post_version', [
            'post_id' => $postId,
            'version_seq' => $seq,
            'snapshot_json' => $snapshotJson,
            'saved_at' => new DateTime(),
        ], [
            'saved_at' => Types::DATETIME_MUTABLE,
        ]);
    }

    public function recordSlugRedirect(string $postId, string $fromSlug, string $toSlug): void
    {
        $connection = $this->connections->create();
        $connection->insert('blog_slug_redirect', [
            'post_id' => $postId,
            'from_slug' => $fromSlug,
            'to_slug' => $toSlug,
            'created_at' => new DateTime(),
        ], ['created_at' => Types::DATETIME_MUTABLE]);
    }

    public function nextVersionSeq(string $postId): int
    {
        $connection = $this->connections->create();
        $max = $connection->fetchOne('SELECT COALESCE(MAX(version_seq), 0) FROM blog_post_version WHERE post_id = :id', ['id' => $postId]);

        return ((int) $max) + 1;
    }
}

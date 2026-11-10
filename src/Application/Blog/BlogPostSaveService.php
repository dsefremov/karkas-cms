<?php

declare(strict_types=1);

namespace Karkas\Application\Blog;

use Karkas\Adapters\Infrastructure\Persistence\ModuleInstanceRepository;
use Karkas\Adapters\Infrastructure\Persistence\ModuleMountRepository;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSiteRepository;
use RuntimeException;

final class BlogPostSaveService
{
    public const int MAX_VERSION_HISTORY = 10;

    public function __construct(
        private readonly BlogPostRepository $posts,
        private readonly ModuleInstanceRepository $instances,
        private readonly ModuleMountRepository $mounts,
    ) {
    }

    /**
     * @param array{title: string, slug: string, body_text: string, activity?: string} $payload
     *
     * @return array<string, mixed>
     */
    public function createDraft(array $payload): array
    {
        $instance = $this->requireBlogInstance();
        $id = bin2hex(random_bytes(16));
        $activity = $payload['activity'] ?? 'inactive';
        $etag = $this->buildEtag($payload);
        $this->posts->insert(
            $id,
            PlatformSiteRepository::DEFAULT_SITE_ID,
            $instance['id'],
            $payload['slug'],
            $payload['title'],
            $payload['body_text'],
            $activity,
            $etag,
        );
        $this->posts->appendVersion($id, 1, json_encode($payload, JSON_THROW_ON_ERROR));

        return ['id' => $id, 'content_etag' => $etag, 'activity' => $activity, 'noop' => false];
    }

    /**
     * @param array{title: string, slug: string, body_text: string, activity: string, content_etag?: string} $payload
     *
     * @return array<string, mixed>
     */
    public function save(string $postId, array $payload): array
    {
        $existing = $this->posts->find($postId);
        if (null === $existing) {
            throw new RuntimeException('Post not found.');
        }
        $etag = $this->buildEtag($payload);
        if ($etag === (string) $existing['content_etag']) {
            return ['id' => $postId, 'content_etag' => $etag, 'noop' => true];
        }
        $fromSlug = (string) $existing['slug'];
        $toSlug = $payload['slug'];
        $this->posts->update($postId, $toSlug, $payload['title'], $payload['body_text'], $payload['activity'], $etag);
        if ($fromSlug !== $toSlug) {
            $this->posts->recordSlugRedirect($postId, $fromSlug, $toSlug);
        }
        $seq = $this->posts->nextVersionSeq($postId);
        $this->posts->appendVersion($postId, $seq, json_encode($payload, JSON_THROW_ON_ERROR));
        $this->posts->pruneVersionsKeepingLast($postId, self::MAX_VERSION_HISTORY);
        $versionCount = $this->posts->countVersions($postId);

        return [
            'id' => $postId,
            'content_etag' => $etag,
            'noop' => false,
            'slug_redirect' => $fromSlug !== $toSlug,
            'version_count' => $versionCount,
        ];
    }

    /**
     * @return list<array{version_seq: int, saved_at: string, title: string, activity: string}>
     */
    public function listVersionHistory(string $postId): array
    {
        if (null === $this->posts->find($postId)) {
            throw new RuntimeException('Post not found.');
        }
        $rows = $this->posts->listVersions($postId, self::MAX_VERSION_HISTORY);
        $history = [];
        foreach ($rows as $row) {
            /** @var array<string, mixed> $snapshot */
            $snapshot = json_decode((string) $row['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
            $history[] = [
                'version_seq' => (int) $row['version_seq'],
                'saved_at' => (string) $row['saved_at'],
                'title' => (string) ($snapshot['title'] ?? ''),
                'activity' => (string) ($snapshot['activity'] ?? 'inactive'),
            ];
        }

        return $history;
    }

    /**
     * @return array{id: string, site_id: int, package_id: string}
     */
    private function requireBlogInstance(): array
    {
        $instance = $this->instances->findBySiteModule(PlatformSiteRepository::DEFAULT_SITE_ID, 'karkas/blog');
        if (null === $instance) {
            throw new RuntimeException('Blog module is not enabled on the default site.');
        }
        $mount = $this->mounts->resolveByPrefix(PlatformSiteRepository::DEFAULT_SITE_ID, '/blog');
        if (null === $mount) {
            throw new RuntimeException('Blog mount is not configured.');
        }

        return $instance;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildEtag(array $payload): string
    {
        $normalized = json_encode([
            'title' => $payload['title'],
            'slug' => $payload['slug'],
            'body_text' => $payload['body_text'],
            'activity' => $payload['activity'] ?? 'inactive',
        ], JSON_THROW_ON_ERROR);

        return hash('sha256', $normalized);
    }
}

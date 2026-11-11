<?php

declare(strict_types=1);

namespace Karkas\Application\Admin;

use DateTimeImmutable;
use DateTimeZone;
use Karkas\Adapters\Infrastructure\Persistence\ModuleInstanceRepository;
use Karkas\Adapters\Infrastructure\Persistence\OperationJournalRepository;
use Karkas\Adapters\Infrastructure\Persistence\PlatformSiteRepository;
use Karkas\Application\Blog\BlogPostRepository;
use Karkas\Application\Content\SitePageRepository;
use RuntimeException;

final class AdminDashboardService
{
    private const TZ = 'Europe/Moscow';

    public function __construct(
        private readonly PlatformSiteRepository $sites,
        private readonly ModuleInstanceRepository $instances,
        private readonly BlogPostRepository $posts,
        private readonly SitePageRepository $pages,
        private readonly OperationJournalRepository $journal,
        private readonly OperationJournalPresenter $presenter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $default = $this->sites->getDefault();
        if (null === $default) {
            throw new RuntimeException('Default site is not configured.');
        }
        $siteId = (int) $default['id'];

        $postsActive = 0;
        $postsInactive = 0;
        $postsChangedToday = 0;
        $recentPosts = [];

        $instance = $this->instances->findBySiteModule($siteId, 'karkas/blog');
        if (null !== $instance) {
            $instanceId = (string) $instance['id'];
            $postsActive = $this->posts->countByActivity($siteId, $instanceId, 'active');
            $postsInactive = $this->posts->countByActivity($siteId, $instanceId, 'inactive');
            $postsChangedToday = $this->posts->countUpdatedOnLocalDay($siteId, $instanceId, self::TZ);
            $recentPosts = $this->posts->listRecentForAdmin($siteId, $instanceId, 5);
            $recentPosts = array_map(fn (array $row): array => $this->presentPost($row), $recentPosts);
        }

        $pagesCount = $this->pages->countBySite($siteId);
        $operations = array_map(
            fn (array $row): array => $this->presenter->present($row),
            $this->journal->listRecent(5),
        );

        return [
            'site' => [
                'id' => $siteId,
                'label' => (string) $default['label'],
                'site_key' => (string) $default['site_key'],
            ],
            'posts_active' => $postsActive,
            'posts_inactive' => $postsInactive,
            'posts_changed_today' => $postsChangedToday,
            'pages_count' => $pagesCount,
            'recent_posts' => $recentPosts,
            'recent_operations' => $operations,
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function presentPost(array $row): array
    {
        $updated = $this->formatTimestamp((string) ($row['updated_at'] ?? ''));
        $activity = (string) ($row['activity'] ?? 'inactive');

        return [
            'id' => (string) $row['id'],
            'title' => (string) $row['title'],
            'slug' => (string) $row['slug'],
            'activity' => $activity,
            'activity_label' => $activity === 'active' ? 'Активна' : 'Неактивна',
            'updated_at' => $updated,
        ];
    }

    private function formatTimestamp(string $raw): string
    {
        if ($raw === '') {
            return '—';
        }
        try {
            $dt = new DateTimeImmutable($raw);
        } catch (\Throwable) {
            $stripped = preg_replace('/\.\d+/', '', $raw) ?? $raw;
            try {
                $dt = new DateTimeImmutable($stripped);
            } catch (\Throwable) {
                return $raw;
            }
        }

        return $dt->setTimezone(new DateTimeZone(self::TZ))->format('j M Y, H:i').' МСК';
    }
}

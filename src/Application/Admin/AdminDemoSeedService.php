<?php

declare(strict_types=1);

namespace Karkas\Application\Admin;

use Karkas\Adapters\Infrastructure\Persistence\PlatformSiteRepository;
use Karkas\Application\Blog\BlogPostSaveService;
use Karkas\Application\Content\SitePageRepository;
use Karkas\Application\Content\SitePageService;
use Karkas\Domain\Content\SitePageTree;
use RuntimeException;

/**
 * Idempotent demo dataset for docs screenshots (dev/test only).
 */
final class AdminDemoSeedService
{
    public function __construct(
        private readonly PlatformSiteRepository $sites,
        private readonly SitePageService $pages,
        private readonly SitePageRepository $pageRepo,
        private readonly BlogPostSaveService $blogPosts,
    ) {
    }

    /**
     * @return array{pages_created: int, posts_created: int, site_label: string}
     */
    public function seed(): array
    {
        $default = $this->sites->getDefault();
        if (null === $default) {
            throw new RuntimeException('Default site is not configured.');
        }
        $siteId = (int) $default['id'];
        $this->sites->updateLabel($siteId, 'Журнал «Каркас»');

        $pagesCreated = $this->seedPages($siteId);
        $postsCreated = $this->seedPosts();

        return [
            'pages_created' => $pagesCreated,
            'posts_created' => $postsCreated,
            'site_label' => 'Журнал «Каркас»',
        ];
    }

    private function seedPages(int $siteId): int
    {
        $created = 0;
        $root = SitePageTree::SENTINEL_PARENT_ID;

        $about = $this->ensurePage($siteId, $root, 'about', 'О проекте', 'active', $created);
        $this->ensurePage($siteId, $about['id'], 'journal', 'О журнале', 'active', $created);
        $editorial = $this->ensurePage($siteId, $about['id'], 'editorial', 'Редакция', 'active', $created);
        $this->ensurePage($siteId, $editorial['id'], 'approach', 'Наш подход', 'active', $created);
        $this->ensurePage($siteId, $about['id'], 'team', 'Команда', 'active', $created);
        $this->ensurePage($siteId, $root, 'contacts', 'Контакты', 'active', $created);
        $this->ensurePage($siteId, $root, 'privacy', 'Политика конфиденциальности', 'inactive', $created);
        // Узел «Журнал» для привязки blog — через UI binding, не отдельная страница /blog (mount).

        return $created;
    }

    private function seedPosts(): int
    {
        $created = 0;
        $samples = [
            ['title' => 'Пространство для главного: как находить ясность', 'slug' => 'space-for-clarity', 'activity' => 'active'],
            ['title' => 'Как сохранять контекст работы', 'slug' => 'preserve-work-context', 'activity' => 'inactive'],
            ['title' => 'Наблюдать, чтобы замечать', 'slug' => 'observe-to-notice', 'activity' => 'inactive'],
            ['title' => 'Порядок, который помогает создавать', 'slug' => 'order-for-ideas', 'activity' => 'active'],
        ];
        foreach ($samples as $sample) {
            if ($this->postExists($sample['slug'])) {
                continue;
            }
            $this->blogPosts->createDraft([
                'title' => $sample['title'],
                'slug' => $sample['slug'],
                'body_text' => $sample['title'],
                'activity' => $sample['activity'],
            ]);
            ++$created;
        }

        return $created;
    }

    private function postExists(string $slug): bool
    {
        try {
            foreach ($this->blogPosts->listPostsForAdmin() as $row) {
                if ($row['slug'] === $slug) {
                    return true;
                }
            }
        } catch (RuntimeException) {
            return false;
        }

        return false;
    }

    /**
     * @return array{id: string, full_path: string}
     */
    private function ensurePage(
        int $siteId,
        string $parentId,
        string $slug,
        string $title,
        string $activity,
        int &$created,
    ): array {
        $existing = $this->pageRepo->findBySiteSlug($siteId, $parentId, $slug);
        if (null !== $existing) {
            return ['id' => (string) $existing['id'], 'full_path' => (string) $existing['full_path']];
        }
        $page = $this->pages->create($siteId, [
            'title' => $title,
            'slug' => $slug,
            'body_text' => $title,
            'activity' => $activity,
            'parent_id' => $parentId,
        ]);
        ++$created;

        return ['id' => (string) $page['id'], 'full_path' => (string) $page['full_path']];
    }
}

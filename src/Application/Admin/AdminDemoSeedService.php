<?php

declare(strict_types=1);

namespace Karkas\Application\Admin;

use DateTimeImmutable;
use DateTimeZone;
use Karkas\Adapters\Infrastructure\Persistence\DatabaseConnectionFactory;
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
        private readonly DatabaseConnectionFactory $connections,
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

    /**
     * Демо-записи для скриншотов: разные даты создания/изменения (за последние недели),
     * больше одной страницы списка (10 на страницу). Даты хранятся в UTC, как и у обычных записей.
     */
    private function seedPosts(): int
    {
        $created = 0;
        // [title, slug, activity, created: days ago + HH:MM UTC, updated: days ago + HH:MM UTC]
        $samples = [
            ['Пространство для главного: как находить ясность', 'space-for-clarity', 'active', [18, '11:30'], [1, '11:40']],
            ['Как сохранять контекст работы', 'preserve-work-context', 'inactive', [17, '14:10'], [2, '09:05']],
            ['Наблюдать, чтобы замечать', 'observe-to-notice', 'inactive', [16, '15:45'], [16, '16:02']],
            ['Порядок, который помогает создавать', 'order-for-ideas', 'active', [15, '09:00'], [3, '12:15']],
            ['Меньше интерфейса, больше смысла', 'less-interface', 'active', [14, '10:20'], [4, '07:30']],
            ['Пять вопросов перед новым проектом', 'five-questions', 'inactive', [12, '13:00'], [12, '13:40']],
            ['Тишина как рабочий инструмент', 'silence-as-tool', 'active', [11, '08:15'], [5, '10:00']],
            ['Заметки на полях: как вести черновики', 'margin-notes', 'active', [9, '16:30'], [6, '15:20']],
            ['Редакционный календарь без стресса', 'editorial-calendar', 'inactive', [8, '12:00'], [7, '08:45']],
            ['Как писать короче', 'write-shorter', 'active', [6, '09:40'], [2, '14:30']],
            ['Ритм публикаций и усталость читателя', 'publishing-rhythm', 'active', [4, '11:10'], [1, '09:15']],
            ['Ошибки, которые стоит повторить', 'mistakes-worth-repeating', 'inactive', [2, '17:25'], [0, '08:50']],
        ];
        $utc = new DateTimeZone('UTC');
        $today = new DateTimeImmutable('today', $utc);
        foreach ($samples as [$title, $slug, $activity, $createdAt, $updatedAt]) {
            if ($this->postExists($slug)) {
                continue;
            }
            $result = $this->blogPosts->createDraft([
                'title' => $title,
                'slug' => $slug,
                'body_text' => $title,
                'activity' => $activity,
            ]);
            $this->backdate((string) $result['id'], $today, $createdAt, $updatedAt);
            ++$created;
        }

        return $created;
    }

    /**
     * @param array{0: int, 1: string} $createdAt
     * @param array{0: int, 1: string} $updatedAt
     */
    private function backdate(string $postId, DateTimeImmutable $today, array $createdAt, array $updatedAt): void
    {
        $at = static function (array $spec) use ($today): string {
            [$h, $m] = array_map('intval', explode(':', $spec[1]));

            return $today->modify(sprintf('-%d days', $spec[0]))->setTime($h, $m)->format('Y-m-d H:i:s.u');
        };
        $this->connections->create()->update('blog_post', [
            'created_at' => $at($createdAt),
            'updated_at' => $at($updatedAt),
        ], ['id' => $postId]);
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

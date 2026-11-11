<?php

declare(strict_types=1);

namespace Karkas\Application\Blog;

use Karkas\Adapters\Infrastructure\Persistence\ModuleInstanceRepository;
use Karkas\Adapters\Infrastructure\Persistence\ModuleMountRepository;
use Karkas\Application\Content\SitePageRepository;
use Karkas\Domain\Content\SitePageException;
use Karkas\Domain\Content\SitePageTree;
use RuntimeException;

final class BlogFacadeService
{
    public const string BLOG_PUBLIC_PREFIX = '/blog';

    public function __construct(
        private readonly ModuleMountRepository $mounts,
        private readonly ModuleInstanceRepository $instances,
        private readonly SitePageRepository $pages,
    ) {
    }

    /**
     * @return array{
     *   bound_page_id: string|null,
     *   last_facade_page_id: string|null,
     *   facade_tree_path: string|null,
     *   public_url: string
     * }|null
     */
    public function describeForSite(int $siteId): ?array
    {
        $instance = $this->instances->findBySiteModule($siteId, 'karkas/blog');
        if (null === $instance) {
            return null;
        }
        $mount = $this->mounts->findBlogMount($siteId);
        if (null === $mount) {
            return null;
        }
        $boundId = $mount['bound_page_id'];
        $lastId = $this->instances->getLastFacadePageId($instance['id']);
        $treePath = null;
        if (null !== $boundId) {
            $page = $this->pages->findById($boundId);
            $treePath = null !== $page ? (string) $page['full_path'] : null;
        } elseif (null !== $lastId) {
            $page = $this->pages->findById($lastId);
            $treePath = null !== $page ? (string) $page['full_path'] : null;
        }

        return [
            'bound_page_id' => $boundId,
            'last_facade_page_id' => $lastId,
            'facade_tree_path' => $treePath,
            'public_url' => self::BLOG_PUBLIC_PREFIX,
        ];
    }

    public function bindPage(int $siteId, string $pageId): void
    {
        $instance = $this->requireBlogInstance($siteId);
        $mount = $this->requireBlogMount($siteId);
        $page = $this->pages->findById($pageId);
        if (null === $page || (int) $page['site_id'] !== $siteId) {
            throw new SitePageException('not_found', 'Страница не найдена на этом сайте.');
        }
        $fullPath = SitePageTree::normalizePath((string) $page['full_path']);
        if ('blog' === $fullPath || str_starts_with($fullPath, 'blog/')) {
            throw new SitePageException('mount_conflict', 'Нельзя привязать блог к пути под префиксом «/blog».');
        }
        $this->mounts->setBoundPage($mount['id'], $pageId);
        $this->instances->setLastFacadePageId($instance['id'], null);
    }

    public function unbindPage(int $siteId): void
    {
        $instance = $this->requireBlogInstance($siteId);
        $mount = $this->requireBlogMount($siteId);
        $boundId = $mount['bound_page_id'];
        if (null === $boundId) {
            throw new RuntimeException('blog_not_bound');
        }
        $this->instances->setLastFacadePageId($instance['id'], $boundId);
        $this->mounts->clearBoundPage($mount['id']);
    }

    public function isBoundFacadePage(int $siteId, string $pageId): bool
    {
        $mount = $this->mounts->findBlogMount($siteId);
        if (null === $mount) {
            return false;
        }

        return null !== $mount['bound_page_id'] && $mount['bound_page_id'] === $pageId;
    }

    /**
     * @return array{action: string, location?: string}|array{action: 'render_list'}
     */
    public function resolveBlogListRequest(int $siteId): array
    {
        $mount = $this->mounts->findBlogMount($siteId);
        if (null === $mount) {
            return ['action' => 'missing'];
        }
        if (null !== $mount['bound_page_id']) {
            return ['action' => 'render_list'];
        }
        $instance = $this->instances->findBySiteModule($siteId, 'karkas/blog');
        if (null === $instance) {
            return ['action' => 'missing'];
        }
        $lastId = $this->instances->getLastFacadePageId($instance['id']);
        if (null === $lastId) {
            return ['action' => 'render_list'];
        }
        $page = $this->pages->findById($lastId);
        if (null === $page || (int) $page['site_id'] !== $siteId) {
            return ['action' => 'render_list'];
        }
        $path = SitePageTree::normalizePath((string) $page['full_path']);

        return ['action' => 'redirect', 'location' => '/'.$path];
    }

    /**
     * @param array<string, array{from: string, to: string}> $pathChanges
     *
     * @return array{bound_page_id: string|null, tree_path: string|null, note?: string}
     */
    public function movePreviewContext(int $siteId, string $pageId, array $pathChanges): array
    {
        $mount = $this->mounts->findBlogMount($siteId);
        if (null === $mount || null === $mount['bound_page_id']) {
            return ['bound_page_id' => null, 'tree_path' => null];
        }
        if ($mount['bound_page_id'] !== $pageId && !isset($pathChanges[$mount['bound_page_id']])) {
            return ['bound_page_id' => $mount['bound_page_id'], 'tree_path' => null];
        }
        $boundId = $mount['bound_page_id'];
        $newPath = $pathChanges[$boundId]['to'] ?? null;
        if (null === $newPath) {
            $row = $this->pages->findById($boundId);

            return [
                'bound_page_id' => $boundId,
                'tree_path' => null !== $row ? (string) $row['full_path'] : null,
            ];
        }

        return [
            'bound_page_id' => $boundId,
            'tree_path' => $newPath,
            'note' => 'Публичный вход блога остаётся на /blog; путь дерева фасада изменится и будет вести на /blog.',
        ];
    }

    /**
     * @return array{id: string, site_id: int, package_id: string}
     */
    private function requireBlogInstance(int $siteId): array
    {
        $instance = $this->instances->findBySiteModule($siteId, 'karkas/blog');
        if (null === $instance) {
            throw new RuntimeException('blog_not_enabled');
        }

        return $instance;
    }

    /**
     * @return array{id: string, site_id: int, module_instance_id: string, mount_kind: string, path_prefix: string, bound_page_id: string|null}
     */
    private function requireBlogMount(int $siteId): array
    {
        $mount = $this->mounts->findBlogMount($siteId);
        if (null === $mount) {
            throw new RuntimeException('blog_mount_missing');
        }

        return $mount;
    }
}

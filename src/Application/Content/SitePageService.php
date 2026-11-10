<?php

declare(strict_types=1);

namespace Karkas\Application\Content;

use Karkas\Adapters\Infrastructure\Persistence\ModuleMountRepository;
use Karkas\Domain\Content\SitePageException;
use Karkas\Domain\Content\SitePageTree;

final class SitePageService
{
    public function __construct(
        private readonly SitePageRepository $pages,
        private readonly ModuleMountRepository $mounts,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tree(int $siteId): array
    {
        $rows = $this->pages->listBySite($siteId);
        /** @var array<string, list<array<string, mixed>>> $byParent */
        $byParent = [];
        foreach ($rows as $row) {
            $parent = (string) $row['parent_id'];
            $byParent[$parent][] = $row;
        }

        return $this->buildTreeNodes($byParent, SitePageTree::SENTINEL_PARENT_ID);
    }

    /**
     * @param array<string, list<array<string, mixed>>> $byParent
     *
     * @return list<array<string, mixed>>
     */
    private function buildTreeNodes(array $byParent, string $parentId): array
    {
        $children = $byParent[$parentId] ?? [];
        $nodes = [];
        foreach ($children as $child) {
            $id = (string) $child['id'];
            $nodes[] = [
                'id' => $id,
                'parent_id' => (string) $child['parent_id'],
                'slug' => (string) $child['slug'],
                'sort_order' => (int) $child['sort_order'],
                'title' => (string) $child['title'],
                'activity' => (string) $child['activity'],
                'full_path' => (string) $child['full_path'],
                'children' => $this->buildTreeNodes($byParent, $id),
            ];
        }

        return $nodes;
    }

    /**
     * @param array{title: string, slug: string, body_text?: string, activity?: string, parent_id?: string} $payload
     *
     * @return array<string, mixed>
     */
    public function create(int $siteId, array $payload): array
    {
        $parentId = (string) ($payload['parent_id'] ?? SitePageTree::SENTINEL_PARENT_ID);
        $slug = strtolower(trim((string) $payload['slug']));
        SitePageTree::assertValidSlug($slug);
        $this->assertParentInSite($siteId, $parentId);

        $title = trim((string) $payload['title']);
        if ('' === $title) {
            throw new SitePageException('invalid_title', 'Укажите заголовок страницы.');
        }
        $body = (string) ($payload['body_text'] ?? '');
        $activity = (string) ($payload['activity'] ?? 'inactive');
        if (!in_array($activity, ['active', 'inactive'], true)) {
            throw new SitePageException('invalid_activity', 'Активность должна быть active или inactive.');
        }
        if ($this->pages->siblingSlugExists($siteId, $parentId, $slug, null)) {
            throw new SitePageException('slug_conflict', 'Страница с таким slug уже есть среди соседей.');
        }

        $fullPath = $this->computePathForChild($siteId, $parentId, $slug);
        $this->assertPathAvailable($siteId, $fullPath, null);

        $id = $this->newId();
        $sortOrder = $this->pages->nextSortOrder($siteId, $parentId);
        $this->pages->insert($id, $siteId, $parentId, $slug, $sortOrder, $title, $body, $activity, $fullPath);

        return $this->pageDto($this->pages->findById($id) ?? []);
    }

    /**
     * @param array{title: string, slug: string, body_text: string, activity: string} $payload
     *
     * @return array<string, mixed>
     */
    public function save(string $pageId, array $payload): array
    {
        $existing = $this->pages->findById($pageId);
        if (null === $existing) {
            throw new SitePageException('not_found', 'Страница не найдена.');
        }
        $siteId = (int) $existing['site_id'];
        $parentId = (string) $existing['parent_id'];
        $slug = strtolower(trim((string) $payload['slug']));
        SitePageTree::assertValidSlug($slug);
        if ($this->pages->siblingSlugExists($siteId, $parentId, $slug, $pageId)) {
            throw new SitePageException('slug_conflict', 'Страница с таким slug уже есть среди соседей.');
        }

        $oldPath = (string) $existing['full_path'];
        $newPath = $this->computePathForChild($siteId, $parentId, $slug);
        $title = trim((string) $payload['title']);
        if ('' === $title) {
            throw new SitePageException('invalid_title', 'Укажите заголовок страницы.');
        }
        $activity = (string) $payload['activity'];
        if (!in_array($activity, ['active', 'inactive'], true)) {
            throw new SitePageException('invalid_activity', 'Активность должна быть active или inactive.');
        }

        $connection = $this->pages->connection();
        $connection->beginTransaction();
        try {
            $this->pages->lockSite($siteId);
            if ($oldPath !== $newPath) {
                $this->assertPathAvailable($siteId, $newPath, $pageId);
                $this->pages->update(
                    $pageId,
                    $slug,
                    (int) $existing['sort_order'],
                    $title,
                    (string) $payload['body_text'],
                    $activity,
                    $newPath,
                    $parentId,
                );
                $redirectCount = $this->rewriteSubtreePaths($siteId, $pageId, $oldPath, $newPath);
            } else {
                $this->pages->update(
                    $pageId,
                    $slug,
                    (int) $existing['sort_order'],
                    $title,
                    (string) $payload['body_text'],
                    $activity,
                    $newPath,
                    $parentId,
                );
                $redirectCount = 0;
            }
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

        return [
            ...$this->pageDto($this->pages->findById($pageId) ?? []),
            'redirects_recorded' => $redirectCount,
        ];
    }

    public function delete(string $pageId): void
    {
        $existing = $this->pages->findById($pageId);
        if (null === $existing) {
            throw new SitePageException('not_found', 'Страница не найдена.');
        }
        $siteId = (int) $existing['site_id'];
        if ($this->pages->countChildren($siteId, $pageId) > 0) {
            throw new SitePageException('has_children', 'Удалите или переместите дочерние страницы.');
        }
        $this->pages->delete($pageId);
    }

    /**
     * @return array<string, mixed>
     */
    public function movePreview(string $pageId, string $newParentId, ?string $newSlug): array
    {
        $plan = $this->planMove($pageId, $newParentId, $newSlug);

        $changed = array_filter(
            $plan['path_changes'],
            static fn (array $c): bool => $c['from'] !== $c['to'],
        );

        return [
            'affected_paths' => count($changed),
            'path_changes' => $plan['path_changes'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function move(string $pageId, string $newParentId, int $newSortOrder, ?string $newSlug = null): array
    {
        $plan = $this->planMove($pageId, $newParentId, $newSlug);
        $existing = $this->pages->findById($pageId);
        if (null === $existing) {
            throw new SitePageException('not_found', 'Страница не найдена.');
        }
        $siteId = (int) $existing['site_id'];

        $connection = $this->pages->connection();
        $connection->beginTransaction();
        try {
            $this->pages->lockSite($siteId);
            foreach ($plan['path_validations'] as $path) {
                $this->assertPathAvailableExceptIds($siteId, $path, array_keys($plan['path_changes']));
            }
            $slug = $plan['slug'];
            $this->pages->update(
                $pageId,
                $slug,
                $newSortOrder,
                (string) $existing['title'],
                (string) $existing['body_text'],
                (string) $existing['activity'],
                $plan['path_changes'][$pageId]['to'],
                $newParentId,
            );
            foreach ($plan['path_changes'] as $id => $change) {
                if ($id === $pageId) {
                    continue;
                }
                $this->pages->updatePathOnly($id, $change['to']);
            }
            foreach ($plan['path_changes'] as $id => $change) {
                if ($change['from'] === $change['to']) {
                    continue;
                }
                $this->pages->recordPathRedirect($siteId, $id, $change['from'], $change['to']);
            }
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

        return [
            'page' => $this->pageDto($this->pages->findById($pageId) ?? []),
            'redirects_recorded' => count(array_filter(
                $plan['path_changes'],
                static fn (array $c): bool => $c['from'] !== $c['to'],
            )),
            'affected_paths' => count($plan['path_changes']),
        ];
    }

    /**
     * @return array{slug: string, path_changes: array<string, array{from: string, to: string}>, path_validations: list<string>}
     */
    private function planMove(string $pageId, string $newParentId, ?string $newSlug): array
    {
        $existing = $this->pages->findById($pageId);
        if (null === $existing) {
            throw new SitePageException('not_found', 'Страница не найдена.');
        }
        $siteId = (int) $existing['site_id'];
        $this->assertParentInSite($siteId, $newParentId);
        $this->assertNotCycle($siteId, $pageId, $newParentId);

        $slug = null !== $newSlug ? strtolower(trim($newSlug)) : (string) $existing['slug'];
        SitePageTree::assertValidSlug($slug);
        if ($this->pages->siblingSlugExists($siteId, $newParentId, $slug, $pageId)) {
            throw new SitePageException('slug_conflict', 'Страница с таким slug уже есть среди соседей.');
        }

        $rows = $this->pages->listBySite($siteId);
        /** @var array<string, array<string, mixed>> $byId */
        $byId = [];
        foreach ($rows as $row) {
            $byId[(string) $row['id']] = $row;
        }

        $subtree = $this->collectSubtree($byId, $pageId);
        $byId[$pageId]['parent_id'] = $newParentId;
        $byId[$pageId]['slug'] = $slug;

        /** @var array<string, array{from: string, to: string}> $pathChanges */
        $pathChanges = [];
        /** @var list<string> $validations */
        $validations = [];
        $ordered = [];
        /** @var list<string> $queue */
        $queue = [$pageId];
        /** @var array<string, true> $seen */
        $seen = [];
        while ([] !== $queue) {
            $current = array_shift($queue);
            if (isset($seen[$current])) {
                continue;
            }
            $seen[$current] = true;
            $ordered[] = $current;
            foreach ($byId as $child) {
                $childId = (string) $child['id'];
                if ((string) $child['parent_id'] === $current && isset($subtree[$childId]) && $childId !== $current) {
                    $queue[] = $childId;
                }
            }
        }
        foreach ($ordered as $id) {
            if (!isset($byId[$id])) {
                continue;
            }
            $row = $byId[$id];
            $from = (string) $row['full_path'];
            $to = $this->computePathFromRow($byId, (string) $row['parent_id'], (string) $row['slug']);
            $byId[$id]['full_path'] = $to;
            $pathChanges[$id] = ['from' => $from, 'to' => $to];
            if ($from !== $to) {
                $validations[] = $to;
            }
        }

        return [
            'slug' => $slug,
            'path_changes' => $pathChanges,
            'path_validations' => $validations,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $byId
     *
     * @return array<string, array<string, mixed>>
     */
    private function collectSubtree(array $byId, string $rootId): array
    {
        $result = [];
        $stack = [$rootId];
        while ([] !== $stack) {
            $id = array_pop($stack);
            if (isset($result[$id])) {
                continue;
            }
            if (!isset($byId[$id])) {
                continue;
            }
            $result[$id] = $byId[$id];
            foreach ($byId as $child) {
                if ((string) $child['parent_id'] === $id) {
                    $stack[] = (string) $child['id'];
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, array<string, mixed>> $byId
     */
    private function computePathFromRow(array $byId, string $parentId, string $slug): string
    {
        if (SitePageTree::SENTINEL_PARENT_ID === $parentId) {
            return $slug;
        }
        if (!isset($byId[$parentId])) {
            throw new SitePageException('parent_missing', 'Родительская страница не найдена.');
        }
        $parentPath = (string) $byId[$parentId]['full_path'];

        return '' === $parentPath ? $slug : $parentPath.'/'.$slug;
    }

    private function computePathForChild(int $siteId, string $parentId, string $slug): string
    {
        if (SitePageTree::SENTINEL_PARENT_ID === $parentId) {
            return $slug;
        }
        $parent = $this->pages->findById($parentId);
        if (null === $parent || (int) $parent['site_id'] !== $siteId) {
            throw new SitePageException('parent_missing', 'Родительская страница не найдена.');
        }
        $parentPath = (string) $parent['full_path'];

        return '' === $parentPath ? $slug : $parentPath.'/'.$slug;
    }

    private function rewriteSubtreePaths(int $siteId, string $rootId, string $oldRootPath, string $newRootPath): int
    {
        $rows = $this->pages->listBySite($siteId);
        $redirects = 0;
        foreach ($rows as $row) {
            $path = (string) $row['full_path'];
            $id = (string) $row['id'];
            if ($id !== $rootId && !str_starts_with($path, $oldRootPath.'/')) {
                continue;
            }
            if ($id === $rootId) {
                continue;
            }
            $suffix = substr($path, strlen($oldRootPath));
            $suffix = ltrim($suffix, '/');
            $newPath = '' === $suffix ? $newRootPath : $newRootPath.'/'.$suffix;
            if ($newPath === $path) {
                continue;
            }
            $this->assertPathAvailable($siteId, $newPath, $id);
            $this->pages->updatePathOnly($id, $newPath);
            $this->pages->recordPathRedirect($siteId, $id, $path, $newPath);
            ++$redirects;
        }
        if ($oldRootPath !== $newRootPath) {
            $this->pages->recordPathRedirect($siteId, $rootId, $oldRootPath, $newRootPath);
            ++$redirects;
        }

        return $redirects;
    }

    private function assertParentInSite(int $siteId, string $parentId): void
    {
        if (SitePageTree::SENTINEL_PARENT_ID === $parentId) {
            return;
        }
        $parent = $this->pages->findById($parentId);
        if (null === $parent || (int) $parent['site_id'] !== $siteId) {
            throw new SitePageException('parent_missing', 'Родительская страница не найдена на этом сайте.');
        }
    }

    private function assertNotCycle(int $siteId, string $pageId, string $newParentId): void
    {
        if (SitePageTree::SENTINEL_PARENT_ID === $newParentId) {
            return;
        }
        if ($pageId === $newParentId) {
            throw new SitePageException('cycle', 'Нельзя переместить страницу в саму себя.');
        }
        $cursor = $newParentId;
        while (SitePageTree::SENTINEL_PARENT_ID !== $cursor) {
            if ($cursor === $pageId) {
                throw new SitePageException('cycle', 'Нельзя переместить страницу в своего потомка.');
            }
            $row = $this->pages->findById($cursor);
            if (null === $row || (int) $row['site_id'] !== $siteId) {
                throw new SitePageException('parent_missing', 'Родительская страница не найдена.');
            }
            $cursor = (string) $row['parent_id'];
        }
    }

    /**
     * @param list<string> $exceptPageIds
     */
    private function assertPathAvailableExceptIds(int $siteId, string $fullPath, array $exceptPageIds): void
    {
        $other = $this->pages->findByPath($siteId, $fullPath);
        if (null !== $other && !in_array((string) $other['id'], $exceptPageIds, true)) {
            throw new SitePageException('path_conflict', 'Адрес «'.$fullPath.'» уже занят другой страницей.');
        }
        $this->assertPathNotBlockedByMountsOrReserved($siteId, $fullPath);
    }

    private function assertPathAvailable(int $siteId, string $fullPath, ?string $exceptPageId): void
    {
        $other = $this->pages->findByPath($siteId, $fullPath);
        if (null !== $other && (null === $exceptPageId || (string) $other['id'] !== $exceptPageId)) {
            throw new SitePageException('path_conflict', 'Адрес «'.$fullPath.'» уже занят другой страницей.');
        }
        $this->assertPathNotBlockedByMountsOrReserved($siteId, $fullPath);
    }

    private function assertPathNotBlockedByMountsOrReserved(int $siteId, string $fullPath): void
    {
        foreach ($this->mounts->listPathPrefixesForSite($siteId) as $prefix) {
            if ($this->pathsCollide($fullPath, $prefix)) {
                throw new SitePageException(
                    'mount_conflict',
                    'Адрес конфликтует с модулем по префиксу «/'.$prefix.'».',
                );
            }
        }
        foreach (SitePageTree::RESERVED_SEGMENTS as $reserved) {
            if ($this->pathsCollide($fullPath, $reserved)) {
                throw new SitePageException(
                    'reserved_path',
                    'Сегмент «'.$reserved.'» зарезервирован системой.',
                );
            }
        }
    }

    private function pathsCollide(string $pagePath, string $otherPath): bool
    {
        $pagePath = SitePageTree::normalizePath($pagePath);
        $otherPath = SitePageTree::normalizePath($otherPath);
        if ($pagePath === $otherPath) {
            return true;
        }

        return str_starts_with($pagePath, $otherPath.'/') || str_starts_with($otherPath, $pagePath.'/');
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function pageDto(array $row): array
    {
        return [
            'id' => (string) ($row['id'] ?? ''),
            'site_id' => (int) ($row['site_id'] ?? 0),
            'parent_id' => (string) ($row['parent_id'] ?? ''),
            'slug' => (string) ($row['slug'] ?? ''),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'title' => (string) ($row['title'] ?? ''),
            'body_text' => (string) ($row['body_text'] ?? ''),
            'activity' => (string) ($row['activity'] ?? 'inactive'),
            'full_path' => (string) ($row['full_path'] ?? ''),
        ];
    }

    private function newId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);
        $hex = bin2hex($bytes);

        return substr($hex, 0, 8).'-'.
            substr($hex, 8, 4).'-'.
            substr($hex, 12, 4).'-'.
            substr($hex, 16, 4).'-'.
            substr($hex, 20, 12);
    }
}

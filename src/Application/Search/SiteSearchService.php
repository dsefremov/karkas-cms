<?php

declare(strict_types=1);

namespace Karkas\Application\Search;

use Karkas\Application\Blog\BlogFacadeService;

final class SiteSearchService
{
    public const int MIN_TOKEN_LENGTH = 3;

    public const int DEFAULT_PER_PAGE = 10;

    public const int MAX_PER_PAGE = 30;

    public function __construct(
        private readonly SiteSearchRepository $repository,
        private readonly BlogFacadeService $blogFacade,
    ) {
    }

    /**
     * @return array{
     *   query: string,
     *   status: string,
     *   page: int,
     *   per_page: int,
     *   total: int,
     *   items: list<array{kind: string, title: string, url: string, snippet: string, score: float}>
     * }
     */
    public function search(int $siteId, string $rawQuery, int $page, int $perPage): array
    {
        $query = trim($rawQuery);
        $page = max(1, $page);
        $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));
        if ('' === $query) {
            return [
                'query' => '',
                'status' => 'empty_query',
                'page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'items' => [],
            ];
        }
        if (mb_strlen($query) < self::MIN_TOKEN_LENGTH) {
            return [
                'query' => $query,
                'status' => 'query_too_short',
                'page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'items' => [],
            ];
        }
        $sanitized = preg_replace('/[+\-><()~*"@]+/', ' ', $query) ?? $query;
        $sanitized = preg_replace('/\s+/', ' ', trim($sanitized)) ?? '';
        if ('' === $sanitized || mb_strlen($sanitized) < self::MIN_TOKEN_LENGTH) {
            return [
                'query' => $query,
                'status' => 'query_too_short',
                'page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'items' => [],
            ];
        }

        $offset = ($page - 1) * $perPage;
        $result = $this->repository->search($siteId, $sanitized, $perPage, $offset);
        $facade = $this->blogFacade->describeForSite($siteId);
        $boundPageId = $facade['bound_page_id'] ?? null;
        $items = [];
        foreach ($result['items'] as $row) {
            $body = (string) $row['body_text'];
            $kind = (string) $row['kind'];
            $url = '/'.ltrim((string) $row['url_path'], '/');
            if ('page' === $kind && null !== $boundPageId && $boundPageId === (string) $row['item_id']) {
                $url = BlogFacadeService::BLOG_PUBLIC_PREFIX;
            }
            $items[] = [
                'kind' => $kind,
                'title' => (string) $row['title'],
                'url' => $url,
                'snippet' => $this->buildSnippet($body, $sanitized),
                'score' => (float) $row['score'],
            ];
        }

        return [
            'query' => $query,
            'status' => 'ok',
            'page' => $page,
            'per_page' => $perPage,
            'total' => $result['total'],
            'items' => $items,
        ];
    }

    private function buildSnippet(string $body, string $query): string
    {
        $normalizedBody = preg_replace('/\s+/u', ' ', trim($body)) ?? '';
        if ('' === $normalizedBody) {
            return '';
        }
        $words = preg_split('/\s+/u', strtolower($query)) ?: [];
        $needle = $words[0] ?? '';
        $pos = '' !== $needle ? mb_stripos($normalizedBody, $needle) : false;
        if (false === $pos) {
            return mb_substr($normalizedBody, 0, 200).(mb_strlen($normalizedBody) > 200 ? '…' : '');
        }
        $start = max(0, $pos - 60);
        $excerpt = mb_substr($normalizedBody, $start, 200);
        if ($start > 0) {
            $excerpt = '…'.$excerpt;
        }
        if ($start + 200 < mb_strlen($normalizedBody)) {
            $excerpt .= '…';
        }

        return $excerpt;
    }
}

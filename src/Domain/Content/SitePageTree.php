<?php

declare(strict_types=1);

namespace Karkas\Domain\Content;

final class SitePageTree
{
    public const string SENTINEL_PARENT_ID = '00000000-0000-0000-0000-000000000001';

    /** @var list<string> */
    public const array RESERVED_SEGMENTS = [
        'api',
        'admin',
        'blog',
        'health',
        'install',
    ];

    public const string SLUG_PATTERN = '[a-z0-9][a-z0-9\-]*';

    public static function normalizePath(string $path): string
    {
        $path = trim($path, '/');

        return strtolower($path);
    }

    public static function assertValidSlug(string $slug): void
    {
        if (1 !== preg_match('/^'.self::SLUG_PATTERN.'$/', $slug)) {
            throw new SitePageException('invalid_slug', 'Недопустимый slug страницы.');
        }
    }
}

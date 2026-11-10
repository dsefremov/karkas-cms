<?php

declare(strict_types=1);

namespace Karkas\Application\Site;

use Karkas\Adapters\Infrastructure\Persistence\PlatformSiteRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

final class SiteRequestResolver
{
    private const LOCAL_HOSTS = ['localhost', '127.0.0.1'];

    public function __construct(
        private readonly PlatformSiteRepository $sites,
    ) {
    }

    /**
     * @return array{id: int, site_key: string, label: string, is_default: bool, canonical_host: string|null}
     */
    public function resolvePublicSite(Request $request): array
    {
        $host = strtolower($request->getHost());
        $matched = $this->sites->findByCanonicalHost($host);
        if (null !== $matched) {
            return $matched;
        }
        if (in_array($host, self::LOCAL_HOSTS, true)) {
            $default = $this->sites->getDefault();
            if (null !== $default) {
                return $default;
            }
        }
        throw new RuntimeException('Unknown host for site resolution.');
    }
}

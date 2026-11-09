<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Catalog;

use InvalidArgumentException;
use Karkas\Application\Package\Manifest\ManifestJsonParser;
use Karkas\Domain\Package\Manifest\PackageManifest;
use Karkas\Domain\Platform\SemVerVersion;
use RuntimeException;
use Throwable;

final class PackageCatalogRepository
{
    public function __construct(
        private readonly string $localCatalogDir,
        private readonly ManifestJsonParser $parser,
        private readonly string $installerCoreVersion,
    ) {
    }

    public function loadLatest(string $packageId): PackageManifest
    {
        $version = $this->resolveLatestCatalogVersion($packageId);
        if (null === $version) {
            return $this->loadAtPath($this->packageRoot($packageId).'/manifest.json');
        }

        return $this->loadAtVersion($packageId, $version);
    }

    public function loadForInstallerCore(): PackageManifest
    {
        $version = new SemVerVersion($this->installerCoreVersion);

        return $this->loadAtVersion('karkas/core', $version);
    }

    public function loadAtVersion(string $packageId, SemVerVersion $version): PackageManifest
    {
        $versionedPath = $this->packageRoot($packageId).'/'.$version->canonical.'/manifest.json';
        if (is_file($versionedPath)) {
            return $this->loadAtPath($versionedPath);
        }
        $flatPath = $this->packageRoot($packageId).'/manifest.json';
        if (!is_file($flatPath)) {
            throw new InvalidArgumentException(sprintf('Catalog manifest missing for "%s" at version "%s".', $packageId, $version->canonical));
        }
        $manifest = $this->loadAtPath($flatPath);
        if ($manifest->version->canonical !== $version->canonical) {
            throw new InvalidArgumentException(sprintf('Catalog has no pinned version "%s" for "%s".', $version->canonical, $packageId));
        }

        return $manifest;
    }

    public function packageSourceDirectory(string $packageId, SemVerVersion $version): string
    {
        $versioned = $this->packageRoot($packageId).'/'.$version->canonical;
        if (is_dir($versioned)) {
            return $versioned;
        }

        return $this->packageRoot($packageId);
    }

    public function resolveLatestCatalogVersion(string $packageId): ?SemVerVersion
    {
        $root = $this->packageRoot($packageId);
        if (!is_dir($root)) {
            return null;
        }
        $best = null;
        foreach (scandir($root) ?: [] as $entry) {
            if (!preg_match('/^\d+\.\d+\.\d+/', $entry)) {
                continue;
            }
            $path = $root.'/'.$entry.'/manifest.json';
            if (!is_file($path)) {
                continue;
            }
            try {
                $candidate = $this->loadAtPath($path)->version;
            } catch (Throwable) {
                continue;
            }
            if (null === $best || $this->compare($candidate, $best) > 0) {
                $best = $candidate;
            }
        }
        if (null !== $best) {
            return $best;
        }
        $flat = $root.'/manifest.json';
        if (is_file($flat)) {
            return $this->loadAtPath($flat)->version;
        }

        return null;
    }

    private function compare(SemVerVersion $a, SemVerVersion $b): int
    {
        return version_compare($a->canonical, $b->canonical);
    }

    private function packageRoot(string $packageId): string
    {
        return $this->localCatalogDir.'/'.$packageId;
    }

    private function loadAtPath(string $path): PackageManifest
    {
        $json = file_get_contents($path);
        if (false === $json) {
            throw new RuntimeException('Cannot read catalog manifest.');
        }

        return $this->parser->parse($json);
    }
}

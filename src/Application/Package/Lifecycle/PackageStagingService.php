<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Lifecycle;

use FilesystemIterator;
use Karkas\Application\Package\Manifest\ManifestDigestService;
use Karkas\Application\Package\Manifest\ManifestJsonParser;
use Karkas\Application\Package\Manifest\PackageTreeConsistencyChecker;
use Karkas\Domain\Package\Manifest\PackageManifest;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class PackageStagingService
{
    public function __construct(
        private readonly string $packagesRoot,
        private readonly ManifestJsonParser $parser,
        private readonly PackageTreeConsistencyChecker $treeChecker,
        private readonly ManifestDigestService $digestService,
    ) {
    }

    /**
     * Copies validated source tree into immutable staging path keyed by package/version/digest.
     */
    public function stageFromDirectory(string $sourceDir): StagedPackage
    {
        $manifestPath = rtrim($sourceDir, '/').'/manifest.json';
        $manifestJson = file_get_contents($manifestPath);
        if (false === $manifestJson) {
            throw new RuntimeException('Cannot read manifest.json.');
        }
        if (strlen($manifestJson) > 1_048_576) {
            throw new RuntimeException('manifest.json too large.');
        }
        $manifest = $this->parser->parse($manifestJson);
        $digest = $this->digestService->normalizedDigest($manifestJson);
        $treeErrors = $this->treeChecker->validateDirectory($sourceDir, $manifest);
        if ([] !== $treeErrors) {
            throw new RuntimeException('Package tree inconsistent: '.implode('; ', $treeErrors));
        }

        $relative = $this->buildRelativeStagingPath($manifest, $digest);
        $target = $this->packagesRoot.'/staging/'.$relative;
        $this->copyTree($sourceDir, $target);

        return new StagedPackage($manifest, $digest, $relative, $target);
    }

    private function buildRelativeStagingPath(PackageManifest $manifest, string $digest): string
    {
        return $manifest->componentId->value.'/'.$manifest->version->canonical.'/'.$digest;
    }

    private function copyTree(string $source, string $target): void
    {
        if (is_dir($target)) {
            return;
        }
        if (!mkdir($target, 0o775, true) && !is_dir($target)) {
            throw new RuntimeException('Cannot create staging directory.');
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $item) {
            /** @var SplFileInfo $item */
            $subPath = substr($item->getPathname(), strlen($source) + 1);
            $dest = $target.'/'.$subPath;
            if ($item->isDir()) {
                if (!is_dir($dest) && !mkdir($dest, 0o775, true) && !is_dir($dest)) {
                    throw new RuntimeException('Cannot create staging subdirectory.');
                }
            } elseif (!is_file($dest) && !copy($item->getPathname(), $dest)) {
                throw new RuntimeException('Cannot copy package file.');
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Quarantine;

use FilesystemIterator;
use Karkas\Application\Package\Lifecycle\PackageStagingService;
use Karkas\Application\Package\Lifecycle\StagedPackage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class FolderIngestService
{
    public function __construct(
        private readonly string $incomingRoot,
        private readonly string $quarantineRoot,
        private readonly IncomingFolderIngestor $ingestor,
        private readonly PackageStagingService $staging,
    ) {
    }

    /**
     * @return list<StagedPackage>
     */
    public function ingestReadyFolders(): array
    {
        $result = [];
        foreach ($this->ingestor->discoverReadyPackages($this->incomingRoot) as $folder) {
            $target = $this->quarantineRoot.'/'.basename($folder);
            if (!is_dir($target)) {
                $this->copyTree($folder, $target);
            }
            $result[] = $this->staging->stageFromDirectory($target);
        }

        return $result;
    }

    private function copyTree(string $source, string $target): void
    {
        if (!mkdir($target, 0o775, true) && !is_dir($target)) {
            throw new RuntimeException('Cannot create quarantine directory for folder ingest.');
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $item) {
            $subPath = substr($item->getPathname(), strlen($source) + 1);
            $dest = $target.'/'.$subPath;
            if ($item->isDir()) {
                if (!is_dir($dest) && !mkdir($dest, 0o775, true) && !is_dir($dest)) {
                    throw new RuntimeException('Cannot create subdirectory during folder ingest.');
                }
            } elseif (!copy($item->getPathname(), $dest)) {
                throw new RuntimeException('Cannot copy incoming package file.');
            }
        }
    }
}

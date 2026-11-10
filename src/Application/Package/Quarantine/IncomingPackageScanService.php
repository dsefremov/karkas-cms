<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Quarantine;

final class IncomingPackageScanService
{
    public function __construct(
        private readonly IncomingFolderIngestor $ingestor,
        private readonly FolderIngestService $folderIngest,
        private readonly string $incomingRoot,
    ) {
    }

    /**
     * @return array{discovered: list<string>, staged_package_ids: list<string>}
     */
    public function scanAndIngest(): array
    {
        $discovered = $this->ingestor->discoverReadyPackages($this->incomingRoot);
        $staged = $this->folderIngest->ingestReadyFolders();
        $ids = [];
        foreach ($staged as $package) {
            $ids[] = $package->manifest->componentId->value.'@'.$package->manifest->version->canonical;
        }

        return ['discovered' => $discovered, 'staged_package_ids' => $ids];
    }
}

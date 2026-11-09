<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Quarantine;

final class IncomingFolderIngestor
{
    public const READY_MARKER = 'READY';

    /**
     * @return list<string> upload folder absolute paths ready for ingest
     */
    public function discoverReadyPackages(string $incomingRoot): array
    {
        if (!is_dir($incomingRoot)) {
            return [];
        }
        $ready = [];
        foreach (scandir($incomingRoot) ?: [] as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $dir = $incomingRoot.'/'.$entry;
            if (!is_dir($dir)) {
                continue;
            }
            if (!is_file($dir.'/'.self::READY_MARKER)) {
                continue;
            }
            if (!is_file($dir.'/manifest.json')) {
                continue;
            }
            $ready[] = $dir;
        }

        return $ready;
    }
}

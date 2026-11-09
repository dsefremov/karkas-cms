<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Quarantine;

use Karkas\Domain\Platform\PackageDeliveryLimits;
use ZipArchive;

final class ZipQuarantineExtractor
{
    public function __construct(
        private readonly PackageDeliveryLimits $limits,
        private readonly PackagePathPolicy $pathPolicy,
    ) {
    }

    /**
     * Extracts ZIP to quarantine directory after safety checks.
     *
     * @return list<string> extracted relative paths
     */
    public function extractToQuarantine(string $zipPath, string $quarantineDir): array
    {
        $reasons = [];
        $size = filesize($zipPath);
        if (false === $size || $size > $this->limits->zipMaxCompressedBytes) {
            $reasons[] = 'Compressed archive exceeds size limit.';
        }
        if ([] !== $reasons) {
            throw new UnsafeArchiveException($reasons);
        }

        $started = microtime(true);
        $zip = new ZipArchive();
        if (true !== $zip->open($zipPath)) {
            throw new UnsafeArchiveException(['Cannot open ZIP archive.']);
        }

        if ($zip->numFiles > $this->limits->maxZipEntries) {
            $zip->close();
            throw new UnsafeArchiveException(['ZIP entry count exceeds limit.']);
        }

        $totalUncompressed = 0;
        $manifestBytes = 0;
        $maxRatio = 0;
        $paths = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $stat = $zip->statIndex($i);
            if (false === $stat) {
                continue;
            }
            $name = (string) $stat['name'];
            if (str_ends_with($name, '/')) {
                continue;
            }
            $pathErrors = $this->pathPolicy->validateRelativePath($name);
            if ([] !== $pathErrors) {
                $zip->close();
                throw new UnsafeArchiveException($pathErrors);
            }
            $uncompressed = (int) ($stat['size'] ?? 0);
            if ($uncompressed > $this->limits->maxUncompressedBytesPerFile) {
                $zip->close();
                throw new UnsafeArchiveException(['Uncompressed file exceeds per-file limit: '.$name]);
            }
            $totalUncompressed += $uncompressed;
            if ('manifest.json' === $name) {
                $manifestBytes = $uncompressed;
            }
            if (in_array($name, $paths, true)) {
                $zip->close();
                throw new UnsafeArchiveException(['Duplicate ZIP entry: '.$name]);
            }
            $paths[] = $name;
            if ($size > 0) {
                $maxRatio = max($maxRatio, (int) ceil($uncompressed / max(1, $size)));
            }
        }

        if ($maxRatio > $this->limits->maxCompressionRatio) {
            $zip->close();
            throw new UnsafeArchiveException(['Compression ratio exceeds limit (zip bomb guard).']);
        }

        if ($totalUncompressed > $this->limits->maxUncompressedBytesTotal) {
            $zip->close();
            throw new UnsafeArchiveException(['Total uncompressed size exceeds limit.']);
        }
        if ($manifestBytes > $this->limits->manifestMaxBytes) {
            $zip->close();
            throw new UnsafeArchiveException(['manifest.json exceeds size limit.']);
        }
        if (count($paths) > $this->limits->maxFilesInPackage) {
            $zip->close();
            throw new UnsafeArchiveException(['File count exceeds package limit.']);
        }

        if (!is_dir($quarantineDir) && !mkdir($quarantineDir, 0o775, true) && !is_dir($quarantineDir)) {
            $zip->close();
            throw new UnsafeArchiveException(['Cannot create quarantine directory.']);
        }

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $stat = $zip->statIndex($i);
            if (false === $stat || str_ends_with((string) $stat['name'], '/')) {
                continue;
            }
            $relative = (string) $stat['name'];
            $target = $quarantineDir.'/'.$relative;
            $targetDir = dirname($target);
            if (!is_dir($targetDir) && !mkdir($targetDir, 0o775, true) && !is_dir($targetDir)) {
                $zip->close();
                throw new UnsafeArchiveException(['Cannot create output directory.']);
            }
            $stream = $zip->getStream($relative);
            if (false === $stream) {
                $zip->close();
                throw new UnsafeArchiveException(['Cannot read ZIP entry: '.$relative]);
            }
            $out = fopen($target, 'wb');
            if (false === $out) {
                fclose($stream);
                $zip->close();
                throw new UnsafeArchiveException(['Cannot write quarantined file.']);
            }
            stream_copy_to_stream($stream, $out);
            fclose($stream);
            fclose($out);

            if (microtime(true) - $started > $this->limits->maxProcessingWalltimeSeconds) {
                $zip->close();
                throw new UnsafeArchiveException(['Extraction walltime exceeded.']);
            }
        }

        $zip->close();
        if (!is_file($quarantineDir.'/manifest.json')) {
            throw new UnsafeArchiveException(['manifest.json missing after extraction.']);
        }

        return $paths;
    }
}

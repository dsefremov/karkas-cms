<?php

declare(strict_types=1);

namespace Karkas\Domain\Platform;

/**
 * Configurable ZIP/folder ingestion limits (spec §6; target-budget defaults for shared hosting).
 */
final readonly class PackageDeliveryLimits
{
    public function __construct(
        public int $zipMaxCompressedBytes,
        public int $manifestMaxBytes,
        public int $maxZipEntries,
        public int $maxFilesInPackage,
        public int $maxUncompressedBytesTotal,
        public int $maxUncompressedBytesPerFile,
        public int $maxCompressionRatio,
        public int $maxPathDepth,
        public int $maxPathSegmentBytes,
        public int $maxProcessingWalltimeSeconds,
    ) {
    }

    /**
     * @param array<string, int> $config
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            zipMaxCompressedBytes: $config['zip_max_compressed_bytes'],
            manifestMaxBytes: $config['manifest_max_bytes'],
            maxZipEntries: $config['max_zip_entries'],
            maxFilesInPackage: $config['max_files_in_package'],
            maxUncompressedBytesTotal: $config['max_uncompressed_bytes_total'],
            maxUncompressedBytesPerFile: $config['max_uncompressed_bytes_per_file'],
            maxCompressionRatio: $config['max_compression_ratio'],
            maxPathDepth: $config['max_path_depth'],
            maxPathSegmentBytes: $config['max_path_segment_bytes'],
            maxProcessingWalltimeSeconds: $config['max_processing_walltime_seconds'],
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function toReportArray(): array
    {
        return [
            'zip_max_compressed_bytes' => $this->zipMaxCompressedBytes,
            'manifest_max_bytes' => $this->manifestMaxBytes,
            'max_zip_entries' => $this->maxZipEntries,
            'max_files_in_package' => $this->maxFilesInPackage,
            'max_uncompressed_bytes_total' => $this->maxUncompressedBytesTotal,
            'max_uncompressed_bytes_per_file' => $this->maxUncompressedBytesPerFile,
            'max_compression_ratio' => $this->maxCompressionRatio,
            'max_path_depth' => $this->maxPathDepth,
            'max_path_segment_bytes' => $this->maxPathSegmentBytes,
            'max_processing_walltime_seconds' => $this->maxProcessingWalltimeSeconds,
        ];
    }
}

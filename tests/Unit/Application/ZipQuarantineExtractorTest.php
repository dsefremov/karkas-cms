<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\Application;

use Karkas\Application\Package\Quarantine\PackagePathPolicy;
use Karkas\Application\Package\Quarantine\UnsafeArchiveException;
use Karkas\Application\Package\Quarantine\ZipQuarantineExtractor;
use Karkas\Domain\Platform\PackageDeliveryLimits;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ZipQuarantineExtractorTest extends TestCase
{
    private ZipQuarantineExtractor $extractor;

    protected function setUp(): void
    {
        $limits = PackageDeliveryLimits::fromConfig([
            'zip_max_compressed_bytes' => 1_000_000,
            'manifest_max_bytes' => 1_000_000,
            'max_zip_entries' => 100,
            'max_files_in_package' => 100,
            'max_uncompressed_bytes_total' => 1_000_000,
            'max_uncompressed_bytes_per_file' => 500_000,
            'max_compression_ratio' => 10,
            'max_path_depth' => 12,
            'max_path_segment_bytes' => 120,
            'max_processing_walltime_seconds' => 30,
        ]);
        $this->extractor = new ZipQuarantineExtractor($limits, new PackagePathPolicy($limits));
    }

    public function testRejectsTraversalPath(): void
    {
        $zipPath = sys_get_temp_dir().'/karkas-evil.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('../evil.txt', 'x');
        $zip->addFromString('manifest.json', '{}');
        $zip->close();

        $this->expectException(UnsafeArchiveException::class);
        $this->extractor->extractToQuarantine($zipPath, sys_get_temp_dir().'/karkas-q-'.uniqid());
        @unlink($zipPath);
    }
}

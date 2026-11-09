<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Manifest;

use Karkas\Domain\Package\Manifest\PackageManifest;

/**
 * Compares on-disk package tree against manifest file list (hash/size); no execution.
 */
final class PackageTreeConsistencyChecker
{
    /**
     * @return list<string> errors; empty when consistent
     */
    public function validateDirectory(string $packageRoot, PackageManifest $manifest): array
    {
        $errors = [];

        foreach ($manifest->files as $entry) {
            $absolute = rtrim($packageRoot, '/').'/'.$entry->path;
            if (!is_file($absolute)) {
                $errors[] = sprintf('Missing file "%s".', $entry->path);
                continue;
            }
            $size = filesize($absolute);
            if ($size !== $entry->sizeBytes) {
                $errors[] = sprintf('Size mismatch for "%s" (expected %d, got %s).', $entry->path, $entry->sizeBytes, false === $size ? 'unknown' : (string) $size);
            }
            $hash = hash_file('sha256', $absolute);
            if ($hash !== $entry->sha256) {
                $errors[] = sprintf('SHA256 mismatch for "%s".', $entry->path);
            }
        }

        $manifestPath = rtrim($packageRoot, '/').'/manifest.json';
        if (is_file($manifestPath) && null !== $manifest->manifestDigest) {
            $manifestJson = file_get_contents($manifestPath);
            if (false === $manifestJson) {
                $errors[] = 'Cannot read manifest.json.';
            } else {
                $normalized = json_encode(
                    json_decode($manifestJson, true),
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                );
                if (false === $normalized) {
                    $errors[] = 'manifest.json is not valid JSON.';
                } elseif ($manifest->manifestDigest !== hash('sha256', $normalized)) {
                    $errors[] = 'manifest_digest does not match manifest.json payload.';
                }
            }
        }

        return $errors;
    }
}

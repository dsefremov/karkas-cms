<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Manifest;

use JsonException;

/**
 * Deterministic manifest JSON digest for plan pinning (spec §6; not provenance).
 */
final class ManifestDigestService
{
    public function normalizedDigest(string $json): string
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ManifestValidationException('Cannot digest invalid JSON.', [$e->getMessage()]);
        }

        $encoded = json_encode($decoded, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $encoded);
    }
}

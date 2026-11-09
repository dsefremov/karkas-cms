<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\Application;

use Karkas\Application\Package\Manifest\JsonSchemaManifestValidator;
use PHPUnit\Framework\TestCase;

final class ManifestJsonSchemaTest extends TestCase
{
    public function testExampleManifestsValidateAgainstSchema(): void
    {
        $schema = dirname(__DIR__, 3).'/resources/schemas/cms-package-manifest-v1.schema.json';
        $validator = new JsonSchemaManifestValidator($schema);
        foreach (['karkas/core/manifest.json', 'karkas/blog/manifest.json'] as $relative) {
            $path = dirname(__DIR__, 3).'/resources/package-catalog/'.$relative;
            $decoded = json_decode((string) file_get_contents($path), false, 512, JSON_THROW_ON_ERROR);
            self::assertIsObject($decoded);
            self::assertSame([], $validator->validate($decoded), $relative.' should validate');
        }
    }
}

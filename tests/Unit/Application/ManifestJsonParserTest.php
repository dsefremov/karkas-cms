<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\Application;

use Karkas\Application\Package\Manifest\JsonSchemaManifestValidator;
use Karkas\Application\Package\Manifest\ManifestJsonParser;
use Karkas\Application\Package\Manifest\ManifestValidationException;
use PHPUnit\Framework\TestCase;

final class ManifestJsonParserTest extends TestCase
{
    private ManifestJsonParser $parser;

    protected function setUp(): void
    {
        $schema = dirname(__DIR__, 3).'/resources/schemas/cms-package-manifest-v1.schema.json';
        $this->parser = new ManifestJsonParser(new JsonSchemaManifestValidator($schema));
    }

    public function testParsesCatalogBlogManifest(): void
    {
        $path = dirname(__DIR__, 3).'/resources/package-catalog/karkas/blog/manifest.json';
        $raw = file_get_contents($path);
        self::assertNotFalse($raw);
        $manifest = $this->parser->parse($raw);
        self::assertSame('karkas/blog', $manifest->componentId->value);
        self::assertSame('1.0.0', $manifest->version->canonical);
    }

    public function testRejectsDuplicateFilePaths(): void
    {
        $json = file_get_contents(dirname(__DIR__, 2).'/fixtures/manifests/invalid-duplicate-file.json');
        self::assertNotFalse($json);
        $this->expectException(ManifestValidationException::class);
        $this->parser->parse($json);
    }

    public function testSchemaRejectsUnknownFormat(): void
    {
        $json = file_get_contents(dirname(__DIR__, 2).'/fixtures/manifests/invalid-unsupported-format.json');
        self::assertNotFalse($json);
        $this->expectException(ManifestValidationException::class);
        $this->parser->parse($json);
    }
}

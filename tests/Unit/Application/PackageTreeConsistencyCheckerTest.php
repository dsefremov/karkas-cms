<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\Application;

use Karkas\Application\Package\Manifest\JsonSchemaManifestValidator;
use Karkas\Application\Package\Manifest\ManifestJsonParser;
use Karkas\Application\Package\Manifest\PackageTreeConsistencyChecker;
use PHPUnit\Framework\TestCase;

final class PackageTreeConsistencyCheckerTest extends TestCase
{
    public function testValidatesCatalogBlogTree(): void
    {
        $schema = dirname(__DIR__, 3).'/resources/schemas/cms-package-manifest-v1.schema.json';
        $parser = new ManifestJsonParser(new JsonSchemaManifestValidator($schema));
        $root = dirname(__DIR__, 3).'/resources/package-catalog/karkas/blog';
        $manifest = $parser->parse((string) file_get_contents($root.'/manifest.json'));
        $checker = new PackageTreeConsistencyChecker();
        self::assertSame([], $checker->validateDirectory($root, $manifest));
    }
}

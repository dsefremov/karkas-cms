<?php

declare(strict_types=1);

namespace Karkas\Tests\Unit\Application;

use Karkas\Application\Package\Compatibility\SemVerConstraintEvaluator;
use Karkas\Application\Package\Compatibility\WholePlanCompatibilityChecker;
use Karkas\Application\Package\Manifest\JsonSchemaManifestValidator;
use Karkas\Application\Package\Manifest\ManifestJsonParser;
use PHPUnit\Framework\TestCase;

final class WholePlanCompatibilityCheckerTest extends TestCase
{
    private WholePlanCompatibilityChecker $checker;

    private ManifestJsonParser $parser;

    protected function setUp(): void
    {
        $schema = dirname(__DIR__, 3).'/resources/schemas/cms-package-manifest-v1.schema.json';
        $this->parser = new ManifestJsonParser(new JsonSchemaManifestValidator($schema));
        $this->checker = new WholePlanCompatibilityChecker(new SemVerConstraintEvaluator());
    }

    public function testBlogCompatibleWithCorePlan(): void
    {
        $root = dirname(__DIR__, 3).'/resources/package-catalog';
        $core = $this->parser->parse((string) file_get_contents($root.'/karkas/core/manifest.json'));
        $blog = $this->parser->parse((string) file_get_contents($root.'/karkas/blog/manifest.json'));
        $result = $this->checker->evaluatePlan($core, [], [$blog], '8.4.0', '8.0');
        self::assertTrue($result->compatible, implode('; ', $result->errors));
    }

    public function testDetectsMissingDependency(): void
    {
        $root = dirname(__DIR__, 3).'/resources/package-catalog';
        $core = $this->parser->parse((string) file_get_contents($root.'/karkas/core/manifest.json'));
        // inject dependency via modified JSON
        $json = json_decode((string) file_get_contents($root.'/karkas/blog/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $json['dependencies'] = ['karkas/search' => '^1.0.0'];
        $blogWithDep = $this->parser->parse(json_encode($json, JSON_THROW_ON_ERROR));
        $result = $this->checker->evaluatePlan($core, [], [$blogWithDep], '8.4.0', '8.0');
        self::assertFalse($result->compatible);
        self::assertNotEmpty($result->errors);
    }
}

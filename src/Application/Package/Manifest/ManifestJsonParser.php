<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Manifest;

use JsonException;
use Karkas\Domain\Module\ModuleId;
use Karkas\Domain\Package\Manifest\ManifestFileEntry;
use Karkas\Domain\Package\Manifest\ManifestFormat;
use Karkas\Domain\Package\Manifest\PackageManifest;
use Karkas\Domain\Platform\SemVerVersion;
use stdClass;

final class ManifestJsonParser
{
    public function __construct(
        private readonly JsonSchemaManifestValidator $schemaValidator,
    ) {
    }

    public function parse(string $json): PackageManifest
    {
        try {
            $decoded = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ManifestValidationException('Manifest is not valid UTF-8 JSON.', [$e->getMessage()]);
        }

        if (!is_object($decoded)) {
            throw new ManifestValidationException('Manifest root must be a JSON object.', ['Root type is not object.']);
        }

        $schemaErrors = $this->schemaValidator->validate($decoded);
        if ([] !== $schemaErrors) {
            throw new ManifestValidationException('Manifest failed JSON Schema validation.', $schemaErrors);
        }

        /** @var stdClass $decoded */
        $format = ManifestFormat::fromString((string) $decoded->format);
        /** @var stdClass $component */
        $component = $decoded->component;
        $componentType = (string) $component->type;
        if ((ManifestFormat::Core === $format && 'core' !== $componentType)
            || (ManifestFormat::Module === $format && 'module' !== $componentType)) {
            throw new ManifestValidationException('Component type does not match manifest format.');
        }

        /** @var stdClass $requires */
        $requires = $decoded->requires;
        $mysqlServer = null;
        if (isset($requires->mysql) && is_object($requires->mysql) && isset($requires->mysql->server)) {
            $mysqlServer = (string) $requires->mysql->server;
        }

        $extensions = [];
        if (isset($requires->extensions) && is_object($requires->extensions)) {
            foreach (get_object_vars($requires->extensions) as $name => $constraint) {
                $extensions[(string) $name] = (string) $constraint;
            }
        }

        $dependencies = [];
        if (isset($decoded->dependencies) && is_object($decoded->dependencies)) {
            foreach (get_object_vars($decoded->dependencies) as $name => $constraint) {
                $dependencies[(string) $name] = (string) $constraint;
            }
        }

        $conflicts = [];
        if (isset($decoded->conflicts) && is_object($decoded->conflicts)) {
            foreach (get_object_vars($decoded->conflicts) as $name => $constraint) {
                $conflicts[(string) $name] = (string) $constraint;
            }
        }

        $migrations = [];
        if (isset($decoded->migrations) && is_array($decoded->migrations)) {
            /** @var list<stdClass> $migrationRows */
            $migrationRows = $decoded->migrations;
            foreach ($migrationRows as $row) {
                $migrations[] = [
                    'id' => (string) $row->id,
                    'checksum' => (string) $row->checksum,
                ];
            }
        }

        $files = [];
        /** @var list<stdClass> $fileRows */
        $fileRows = $decoded->files;
        foreach ($fileRows as $fileRow) {
            $files[] = new ManifestFileEntry(
                (string) $fileRow->path,
                (int) $fileRow->size_bytes,
                (string) $fileRow->sha256,
            );
        }

        $this->assertFileListSortedUnique($files);

        return new PackageManifest(
            format: $format,
            componentId: new ModuleId((string) $component->id),
            componentType: $componentType,
            version: new SemVerVersion((string) $decoded->version),
            requiresCore: (string) $requires->core,
            requiresSdk: (string) $requires->sdk,
            requiresPhp: (string) $requires->php,
            requiresMysqlServer: $mysqlServer,
            requiredExtensions: $extensions,
            dependencies: $dependencies,
            conflicts: $conflicts,
            migrations: $migrations,
            files: $files,
            manifestDigest: isset($decoded->manifest_digest) ? (string) $decoded->manifest_digest : null,
            displayName: isset($component->display_name) ? (string) $component->display_name : null,
        );
    }

    /**
     * @param list<ManifestFileEntry> $files
     */
    private function assertFileListSortedUnique(array $files): void
    {
        $paths = [];
        $previous = null;
        foreach ($files as $entry) {
            if (in_array($entry->path, $paths, true)) {
                throw new ManifestValidationException(sprintf('Duplicate file path "%s".', $entry->path));
            }
            $paths[] = $entry->path;
            if (null !== $previous && strcmp($entry->path, $previous) < 0) {
                throw new ManifestValidationException('File paths must be sorted ascending byte-order.');
            }
            $previous = $entry->path;
            if ('manifest.json' === $entry->path) {
                throw new ManifestValidationException('manifest.json must not appear in the file list.');
            }
        }
    }
}

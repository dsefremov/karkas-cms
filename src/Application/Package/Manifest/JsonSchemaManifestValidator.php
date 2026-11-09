<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Manifest;

use InvalidArgumentException;
use JsonException;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;

final class JsonSchemaManifestValidator
{
    private readonly Validator $validator;

    public function __construct(
        private readonly string $schemaPath,
    ) {
        if (!is_file($this->schemaPath)) {
            throw new InvalidArgumentException(sprintf('Manifest schema not found at "%s".', $this->schemaPath));
        }
        $this->validator = new Validator();
    }

    /**
     * @return list<string> empty when valid
     */
    public function validate(object $decoded): array
    {
        $schemaJson = file_get_contents($this->schemaPath);
        if (false === $schemaJson) {
            return ['Cannot read manifest JSON Schema file.'];
        }
        try {
            $schema = json_decode($schemaJson, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return ['Manifest JSON Schema file is invalid: '.$e->getMessage()];
        }

        $result = $this->validator->validate($decoded, $schema);

        if ($result->isValid()) {
            return [];
        }

        $error = $result->error();
        if (!$error instanceof ValidationError) {
            return ['Unknown schema validation failure.'];
        }

        return $this->flattenErrors($error);
    }

    /**
     * @return list<string>
     */
    private function flattenErrors(ValidationError $error): array
    {
        $messages = [];
        $pathParts = $error->data()->fullPath();
        $location = [] === $pathParts ? '/' : '/'.implode('/', $pathParts);
        $messages[] = $location.': '.$error->message();
        foreach ($error->subErrors() as $sub) {
            foreach ($this->flattenErrors($sub) as $msg) {
                $messages[] = $msg;
            }
        }

        return $messages;
    }
}

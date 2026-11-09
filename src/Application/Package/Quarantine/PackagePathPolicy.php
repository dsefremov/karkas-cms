<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Quarantine;

use Karkas\Domain\Platform\PackageDeliveryLimits;

final class PackagePathPolicy
{
    public function __construct(
        private readonly PackageDeliveryLimits $limits,
    ) {
    }

    /**
     * @return list<string> empty if valid
     */
    public function validateRelativePath(string $relativePath): array
    {
        $errors = [];
        if ('' === $relativePath || str_starts_with($relativePath, '/')) {
            $errors[] = 'Absolute paths are not allowed.';
        }
        if (str_contains($relativePath, '\\') || str_contains($relativePath, "\0")) {
            $errors[] = 'Invalid path encoding in "'.$relativePath.'".';
        }
        if (1 === preg_match('#(^|/)\.\.(/|$)#', $relativePath) || 1 === preg_match('#(^|/)\.(/|$)#', $relativePath)) {
            $errors[] = 'Dot segments are not allowed in "'.$relativePath.'".';
        }
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._\\/\\-]*$/', $relativePath)) {
            $errors[] = 'Non-ASCII or disallowed characters in "'.$relativePath.'".';
        }
        $segments = explode('/', $relativePath);
        if (count($segments) > $this->limits->maxPathDepth) {
            $errors[] = 'Path depth exceeds limit for "'.$relativePath.'".';
        }
        foreach ($segments as $segment) {
            if (strlen($segment) > $this->limits->maxPathSegmentBytes) {
                $errors[] = 'Path segment too long in "'.$relativePath.'".';
            }
        }

        return $errors;
    }
}

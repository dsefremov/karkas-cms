<?php

declare(strict_types=1);

namespace Karkas\Application\Package\Compatibility;

use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Karkas\Domain\Platform\SemVerVersion;

/**
 * Wraps composer/semver with Karkas initial grammar policy (spec §6).
 */
final class SemVerConstraintEvaluator
{
    private const UNSUPPORTED_PATTERN = '/(?:^|\s)(?:\d+\.\d+|\d+)(?:\s|$)|@|,/';

    public function assertSupportedConstraintSyntax(string $constraint): void
    {
        $trimmed = trim($constraint);
        if (1 === preg_match(self::UNSUPPORTED_PATTERN, $trimmed)) {
            throw new UnsupportedConstraintException(sprintf('Unsupported constraint syntax "%s".', $constraint));
        }
        if (str_contains($trimmed, '-')
            && null === SemVerVersion::tryParse(ltrim($trimmed, '= '))
            && 1 === preg_match('/[<>^~]/', $trimmed)) {
            throw new UnsupportedConstraintException('Prerelease range comparators are not supported in initial grammar.');
        }
    }

    public function satisfies(SemVerVersion $version, string $constraint): bool
    {
        $this->assertSupportedConstraintSyntax($constraint);
        if (!$version->isStable() && !$this->isExactConstraint($constraint, $version)) {
            return false;
        }

        return Semver::satisfies($version->canonical, $constraint);
    }

    public function compare(SemVerVersion $a, SemVerVersion $b): int
    {
        if (Comparator::greaterThan($a->canonical, $b->canonical)) {
            return 1;
        }
        if (Comparator::lessThan($a->canonical, $b->canonical)) {
            return -1;
        }

        return 0;
    }

    private function isExactConstraint(string $constraint, SemVerVersion $version): bool
    {
        $normalized = trim($constraint);
        if (str_starts_with($normalized, '=')) {
            $normalized = trim(substr($normalized, 1));
        }

        return $normalized === $version->canonical;
    }
}

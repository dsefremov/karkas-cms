<?php

declare(strict_types=1);

namespace Karkas\Domain\Package;

/**
 * Trust/compatibility status of a catalogued candidate (metadata is never executable proof).
 */
enum VersionAvailabilityStatus: string
{
    case Compatible = 'compatible';
    case Incompatible = 'incompatible';
    case Untrusted = 'untrusted';
    case Stale = 'stale';
}

<?php

declare(strict_types=1);

namespace Karkas\Domain\Package;

/**
 * Where available-version metadata originated (spec §6; online feed is phase 2).
 */
enum VersionSourceKind: string
{
    case Local = 'local';
    case Imported = 'imported';
    case Unknown = 'unknown';
    case Offline = 'offline';
}

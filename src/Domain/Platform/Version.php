<?php

declare(strict_types=1);

namespace Karkas\Domain\Platform;

/** Product stage marker; SemVer deployment versions come from package manifests (Stage 1+). */
final class Version
{
    public const string STAGE = '1';

    public const string PRODUCT = 'Karkas CMS';
}

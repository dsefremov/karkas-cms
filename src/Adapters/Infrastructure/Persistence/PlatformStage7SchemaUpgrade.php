<?php

declare(strict_types=1);

namespace Karkas\Adapters\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Karkas\Application\Search\SearchIndexService;

final class PlatformStage7SchemaUpgrade
{
    public function __construct(
        private readonly SearchIndexService $searchIndexes,
    ) {
    }

    public function apply(Connection $connection): void
    {
        $this->searchIndexes->ensureFulltextIndexes($connection);
    }
}

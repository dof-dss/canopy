<?php

declare(strict_types=1);

namespace Canopy\Solr;

use Canopy\Inventory\ProjectTarget;
use Canopy\Result\Result;

final readonly class ProjectAudit
{
    /**
     * @param list<Configset> $configsets
     * @param list<Result> $results
     */
    public function __construct(
        public ProjectTarget $project,
        public array $configsets,
        public array $results,
    ) {
    }
}

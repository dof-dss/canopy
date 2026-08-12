<?php

declare(strict_types=1);

namespace Canopy\Editorial;

use Canopy\Inventory\ProjectTarget;

final readonly class EditorialSite
{
    public function __construct(
        public ProjectTarget $project,
        public string $id,
        public string $configPath,
        public string $relativeConfigPath,
    ) {
    }

    public function target(): string
    {
        return sprintf('%s:%s', $this->project->id, $this->id);
    }
}

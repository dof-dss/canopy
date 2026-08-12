<?php

declare(strict_types=1);

namespace Canopy\Check;

final readonly class CheckContext
{
    /**
     * @param array<string, bool> $permissions
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        public string $projectRoot,
        public array $permissions = [],
        public array $configuration = [],
    ) {
    }

    public function permits(string $capability): bool
    {
        return $this->permissions[$capability] ?? false;
    }
}

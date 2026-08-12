<?php

declare(strict_types=1);

namespace Canopy\Inventory;

final readonly class ProjectTarget
{
    /**
     * @param array<string, mixed> $expectations
     */
    public function __construct(
        public string $id,
        public string $path,
        public array $expectations = [],
    ) {
        if ($id === '') {
            throw new \InvalidArgumentException('A project ID must not be empty.');
        }

        if ($path === '') {
            throw new \InvalidArgumentException('A project path must not be empty.');
        }
    }
}

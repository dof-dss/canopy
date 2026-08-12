<?php

declare(strict_types=1);

namespace Canopy\Editorial;

final readonly class EditorialProfile
{
    /**
     * @param array<string, array{label: string, expectation: string, detector: string, values: list<string>, exclude: list<string>}> $capabilities
     */
    public function __construct(
        public string $id,
        public string $label,
        public array $capabilities,
    ) {
    }
}

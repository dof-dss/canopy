<?php

declare(strict_types=1);

namespace Canopy\Editorial;

final readonly class EditorialProfile
{
    /**
     * @param array<string, array{label: string, expectation: string, detector: string, values: list<string>, exclude: list<string>, format: string, core: list<string>, optional: list<string>, optional_reasons: array<string, string>, unexpected: string}> $capabilities
     */
    public function __construct(
        public string $id,
        public string $label,
        public array $capabilities,
    ) {
    }
}

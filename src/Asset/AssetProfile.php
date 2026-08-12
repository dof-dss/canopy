<?php

declare(strict_types=1);

namespace Canopy\Asset;

final readonly class AssetProfile
{
    /** @param array<string, array{label: string, expectation: string, detector: string, values: list<string>}> $capabilities */
    public function __construct(public string $id, public string $label, public array $capabilities)
    {
    }
}

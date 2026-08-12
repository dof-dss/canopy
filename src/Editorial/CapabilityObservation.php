<?php

declare(strict_types=1);

namespace Canopy\Editorial;

final readonly class CapabilityObservation
{
    /** @param array<string, mixed> $evidence */
    public function __construct(
        public bool $satisfied,
        public string $summary,
        public array $evidence,
    ) {
    }
}

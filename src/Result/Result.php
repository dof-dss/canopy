<?php

declare(strict_types=1);

namespace Canopy\Result;

final readonly class Result
{
    /**
     * @param array<string, mixed> $evidence
     */
    public function __construct(
        public string $checkId,
        public Status $status,
        public string $target,
        public string $summary,
        public \DateTimeImmutable $observedAt,
        public array $evidence = [],
        public ?string $remediation = null,
        public ?int $durationMs = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'check_id' => $this->checkId,
            'status' => $this->status->value,
            'target' => $this->target,
            'summary' => $this->summary,
            'observed_at' => $this->observedAt->format(DATE_ATOM),
            'evidence' => $this->evidence,
            'remediation' => $this->remediation,
            'duration_ms' => $this->durationMs,
        ];
    }
}

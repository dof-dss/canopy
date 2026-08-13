<?php

declare(strict_types=1);

namespace Canopy\Output;

use Canopy\Application;
use Canopy\Inventory\ProjectTarget;
use Canopy\Result\Result;
use Canopy\Result\Status;
use Canopy\Security\OutputRedactor;

final class ResultDocument
{
    public function __construct(private readonly OutputRedactor $redactor = new OutputRedactor())
    {
    }

    /**
     * @param list<ProjectTarget> $projects
     * @param list<Result> $results
     *
     * @return array<string, mixed>
     */
    public function build(string $kind, array $projects, array $results): array
    {
        $summary = array_fill_keys(array_column(Status::cases(), 'value'), 0);
        $sensitivePaths = array_map(static fn (ProjectTarget $project): string => $project->path, $projects);

        foreach ($results as $result) {
            ++$summary[$result->status->value];
        }

        return [
            'schema_version' => '1.0',
            'kind' => $kind,
            'generated_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'tool' => [
                'name' => 'canopy',
                'version' => Application::VERSION,
            ],
            'project' => [
                'name' => count($projects) === 1
                    ? $projects[0]->id
                    : ucwords(str_replace('_', ' ', $kind)) . ' estate',
                'repositories' => array_map(
                    static fn (ProjectTarget $project): array => ['id' => $project->id],
                    $projects,
                ),
            ],
            'redaction' => [
                'policy' => 'public-safe-default',
                'repository_paths' => 'omitted',
                'sensitive_values' => 'redacted',
            ],
            'summary' => $summary,
            'results' => array_map(
                fn (Result $result): array => $this->redactor->value($result->toArray(), $sensitivePaths),
                $results,
            ),
        ];
    }
}

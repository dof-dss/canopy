<?php

declare(strict_types=1);

namespace Canopy\Editorial;

use Canopy\Inventory\ProjectTarget;
use Canopy\Result\Result;
use Canopy\Result\Status;

final readonly class EditorialAuditor
{
    public function __construct(
        private EditorialSiteDiscovery $discovery = new EditorialSiteDiscovery(),
        private EditorialConfigInspector $inspector = new EditorialConfigInspector(),
        private EditorialCapabilityEvaluator $evaluator = new EditorialCapabilityEvaluator(),
    ) {
    }

    /**
     * @param list<ProjectTarget> $projects
     *
     * @return list<Result>
     */
    public function audit(array $projects, EditorialProfile $profile): array
    {
        $results = [];
        foreach ($projects as $project) {
            $observedAt = new \DateTimeImmutable();
            if (!is_dir($project->path) || !is_readable($project->path)) {
                $results[] = new Result(
                    'editorial.project.access',
                    Status::Unknown,
                    $project->id,
                    'The project directory is missing or unreadable.',
                    $observedAt,
                    ['path' => $project->path],
                );
                continue;
            }

            $sites = $this->discovery->discover($project);
            $expectedSites = $this->expectedSites($project);
            if ($sites === []) {
                $results[] = new Result(
                    'editorial.config.discovery',
                    Status::Unknown,
                    $project->id,
                    'No Drupal exported configuration directory was discovered.',
                    $observedAt,
                    array_filter([
                        'searched_layouts' => ['config/sync', 'project/config/*/config'],
                        'expected_sites' => $expectedSites,
                    ], static fn (mixed $value): bool => $value !== null),
                    'Add the project config layout to Canopy or correct the inventory path.',
                );
                continue;
            }

            $results[] = new Result(
                'editorial.config.discovery',
                Status::Pass,
                $project->id,
                sprintf('Discovered exported configuration for %d site(s).', count($sites)),
                $observedAt,
                ['sites' => array_map(
                    static fn (EditorialSite $site): array => ['id' => $site->id, 'config_path' => $site->relativeConfigPath],
                    $sites,
                )],
            );

            if ($expectedSites !== null) {
                $discoveredSites = array_map(static fn (EditorialSite $site): string => $site->id, $sites);
                sort($discoveredSites);
                $missingSites = array_values(array_diff($expectedSites, $discoveredSites));
                $unexpectedSites = array_values(array_diff($discoveredSites, $expectedSites));
                $status = $missingSites !== []
                    ? Status::Unknown
                    : ($unexpectedSites !== [] ? Status::Warn : Status::Pass);
                $summary = match ($status) {
                    Status::Unknown => sprintf('Expected site exports were not discovered: %s.', implode(', ', $missingSites)),
                    Status::Warn => sprintf('Discovered sites are absent from the expected inventory: %s.', implode(', ', $unexpectedSites)),
                    default => sprintf('All %d expected site export(s) were discovered.', count($expectedSites)),
                };
                $results[] = new Result(
                    'editorial.config.site_inventory',
                    $status,
                    $project->id,
                    $summary,
                    $observedAt,
                    [
                        'expected_sites' => $expectedSites,
                        'discovered_sites' => $discoveredSites,
                        'missing_sites' => $missingSites,
                        'unexpected_sites' => $unexpectedSites,
                    ],
                    $status === Status::Pass ? null : 'Update the exported configuration or the source-controlled expected site inventory after review.',
                );
            }

            foreach ($sites as $site) {
                try {
                    $snapshot = $this->inspector->inspect($site);
                } catch (\Throwable $exception) {
                    $results[] = new Result(
                        'editorial.config.parse',
                        Status::Unknown,
                        $site->target(),
                        'Exported editorial configuration could not be parsed.',
                        $observedAt,
                        ['config_path' => $site->relativeConfigPath, 'error_type' => $exception::class],
                    );
                    continue;
                }

                foreach ($profile->capabilities as $id => $definition) {
                    $observation = $this->evaluator->evaluate(
                        $definition['detector'],
                        $definition['values'],
                        $snapshot,
                        $definition['exclude'],
                        $definition['format'],
                        $definition['core'],
                        $definition['optional'],
                        $definition['unexpected'],
                        $definition['optional_reasons'],
                    );
                    $status = $observation->satisfied
                        ? Status::Pass
                        : match ($definition['expectation']) {
                            'required' => Status::Fail,
                            'preferred' => Status::Warn,
                            default => Status::Skipped,
                        };
                    $results[] = new Result(
                        'editorial.capability.' . $id,
                        $status,
                        $site->target(),
                        $observation->summary,
                        $observedAt,
                        array_merge($observation->evidence, [
                            'profile' => $profile->id,
                            'capability' => $definition['label'],
                            'expectation' => $definition['expectation'],
                            'config_path' => $site->relativeConfigPath,
                        ]),
                        $observation->satisfied ? null : 'Review whether the capability is absent, implemented differently, or should be recorded as a project exception.',
                    );
                }

                $results[] = new Result(
                    'editorial.inventory.summary',
                    Status::Pass,
                    $site->target(),
                    sprintf('Recorded editorial inventory for %d content types, %d workflows, %d roles, and %d media types.', count($snapshot->nodeRevisionDefaults), count($snapshot->workflows), count($snapshot->rolePermissions), count($snapshot->mediaTypes)),
                    $observedAt,
                    [
                        'content_type_count' => count($snapshot->nodeRevisionDefaults),
                        'workflow_count' => count($snapshot->workflows),
                        'role_count' => count($snapshot->rolePermissions),
                        'media_types' => $snapshot->mediaTypes,
                        'pathauto_pattern_count' => count($snapshot->pathautoPatterns),
                        'metatag_default_count' => count($snapshot->metatagDefaults),
                        'text_formats' => array_keys($snapshot->textFormats),
                    ],
                );
            }
        }

        return $results;
    }

    /** @return list<string>|null */
    private function expectedSites(ProjectTarget $project): ?array
    {
        $editorial = $project->expectations['editorial'] ?? null;
        if (!is_array($editorial) || !array_key_exists('sites', $editorial)) {
            return null;
        }

        $sites = $editorial['sites'];
        if (!is_array($sites)) {
            throw new \InvalidArgumentException(sprintf('Editorial site expectations for %s must be a list.', $project->id));
        }

        $expected = [];
        foreach ($sites as $site) {
            if (!is_string($site) || $site === '') {
                throw new \InvalidArgumentException(sprintf('Editorial site expectations for %s must contain non-empty strings.', $project->id));
            }
            $expected[] = $site;
        }

        $expected = array_values(array_unique($expected));
        sort($expected);

        return $expected;
    }
}

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
            if ($sites === []) {
                $results[] = new Result(
                    'editorial.config.discovery',
                    Status::Unknown,
                    $project->id,
                    'No Drupal exported configuration directory was discovered.',
                    $observedAt,
                    ['searched_layouts' => ['config/sync', 'project/config/*/config']],
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
}

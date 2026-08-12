<?php

declare(strict_types=1);

namespace Canopy\Asset;

use Canopy\Editorial\EditorialSiteDiscovery;
use Canopy\Inventory\ProjectTarget;
use Canopy\Result\Result;
use Canopy\Result\Status;

final readonly class AssetAuditor
{
    public function __construct(
        private EditorialSiteDiscovery $discovery = new EditorialSiteDiscovery(),
        private AssetConfigInspector $inspector = new AssetConfigInspector(),
        private AssetCapabilityEvaluator $evaluator = new AssetCapabilityEvaluator(),
    ) {
    }

    /** @param list<ProjectTarget> $projects
     * @return list<Result>
     */
    public function audit(array $projects, AssetProfile $profile): array
    {
        $results = [];
        foreach ($projects as $project) {
            $observedAt = new \DateTimeImmutable();
            if (!is_dir($project->path) || !is_readable($project->path)) {
                $results[] = new Result('assets.project.access', Status::Unknown, $project->id, 'The project directory is missing or unreadable.', $observedAt, ['path' => $project->path]);
                continue;
            }
            $sites = $this->discovery->discover($project);
            if ($sites === []) {
                $results[] = new Result('assets.config.discovery', Status::Unknown, $project->id, 'No Drupal exported configuration directory was discovered.', $observedAt, ['searched_layouts' => ['config/sync', 'project/config/*/config']]);
                continue;
            }
            $results[] = new Result('assets.config.discovery', Status::Pass, $project->id, sprintf('Discovered exported configuration for %d site(s).', count($sites)), $observedAt, ['sites' => array_map(static fn ($site): array => ['id' => $site->id, 'config_path' => $site->relativeConfigPath], $sites)]);
            foreach ($sites as $site) {
                try {
                    $snapshot = $this->inspector->inspect($site);
                } catch (\Throwable $exception) {
                    $results[] = new Result('assets.config.parse', Status::Unknown, $site->target(), 'Exported asset configuration could not be parsed.', $observedAt, ['config_path' => $site->relativeConfigPath, 'error_type' => $exception::class]);
                    continue;
                }
                foreach ($profile->capabilities as $id => $definition) {
                    $observation = $this->evaluator->evaluate($definition['detector'], $definition['values'], $snapshot);
                    $status = $observation->satisfied ? Status::Pass : match ($definition['expectation']) {
                        'required' => Status::Fail,
                        'preferred' => Status::Warn,
                        default => Status::Skipped,
                    };
                    $results[] = new Result(
                        'assets.capability.' . $id,
                        $status,
                        $site->target(),
                        $observation->summary,
                        $observedAt,
                        array_merge($observation->evidence, ['profile' => $profile->id, 'capability' => $definition['label'], 'expectation' => $definition['expectation'], 'config_path' => $site->relativeConfigPath]),
                        $observation->satisfied ? null : 'Review whether the capability is absent, implemented differently, or should be recorded as a project exception.',
                    );
                }
                $schemes = array_values(array_unique(array_column($snapshot->fieldStorages, 'uri_scheme')));
                sort($schemes);
                $assetFields = array_filter($snapshot->fieldStorages, static fn (array $storage): bool => in_array($storage['type'], ['file', 'image'], true));
                $results[] = new Result('assets.inventory.summary', Status::Pass, $site->target(), sprintf('Recorded %d media types, %d file/image fields, %d image styles, and %d responsive image styles.', count($snapshot->mediaTypes), count($assetFields), count($snapshot->imageStyles), count($snapshot->responsiveImageStyles)), $observedAt, ['media_types' => array_keys($snapshot->mediaTypes), 'file_or_image_field_count' => count($assetFields), 'uri_schemes' => $schemes, 'image_style_count' => count($snapshot->imageStyles), 'responsive_image_style_count' => count($snapshot->responsiveImageStyles)]);
            }
        }
        return $results;
    }
}

<?php

declare(strict_types=1);

namespace Canopy\Editorial;

use Canopy\Inventory\ProjectTarget;

final class EditorialSiteDiscovery
{
    /**
     * @return list<EditorialSite>
     */
    public function discover(ProjectTarget $project): array
    {
        $sites = [];
        $rootConfig = $project->path . '/config/sync';

        if (is_file($rootConfig . '/core.extension.yml')) {
            $sites[] = new EditorialSite(
                $project,
                $project->id,
                $rootConfig,
                'config/sync',
            );
        }

        $multisiteConfigs = glob($project->path . '/project/config/*/config', GLOB_ONLYDIR) ?: [];
        foreach ($multisiteConfigs as $configPath) {
            if (!is_file($configPath . '/core.extension.yml')) {
                continue;
            }

            $sites[] = new EditorialSite(
                $project,
                basename(dirname($configPath)),
                $configPath,
                substr($configPath, strlen(rtrim($project->path, DIRECTORY_SEPARATOR)) + 1),
            );
        }

        usort($sites, static fn (EditorialSite $left, EditorialSite $right): int => $left->id <=> $right->id);

        return $sites;
    }
}

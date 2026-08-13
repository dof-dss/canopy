<?php

declare(strict_types=1);

namespace Canopy\Inventory;

use Symfony\Component\Yaml\Yaml;

final class ProjectInventoryLoader
{
    public const PROJECT_INVENTORY = '.canopy/inventory.yml';

    /**
     * @param list<string> $projectValues
     *
     * @return list<ProjectTarget>
     */
    public function load(array $projectValues, ?string $inventoryPath, string $workingDirectory): array
    {
        $definitions = [];

        if ($projectValues === [] && $inventoryPath === null) {
            $projectInventory = rtrim($workingDirectory, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . self::PROJECT_INVENTORY;
            if (is_file($projectInventory)) {
                $inventoryPath = $projectInventory;
            }
        }

        foreach ($projectValues as $value) {
            $definitions[] = $this->parseCommandLineDefinition($value);
        }

        if ($inventoryPath !== null) {
            $resolvedInventory = $this->resolvePath($inventoryPath, $workingDirectory);

            if (!is_file($resolvedInventory)) {
                throw new \InvalidArgumentException(sprintf('Inventory file does not exist: %s', $resolvedInventory));
            }

            $parsed = Yaml::parseFile($resolvedInventory);
            $projects = is_array($parsed) ? ($parsed['projects'] ?? null) : null;

            if (!is_array($projects)) {
                throw new \InvalidArgumentException('The inventory must contain a projects list.');
            }

            $inventoryDirectory = dirname($resolvedInventory);

            foreach ($projects as $project) {
                if (is_string($project)) {
                    $definitions[] = [
                        'id' => null,
                        'path' => $this->resolvePath($project, $inventoryDirectory),
                        'expectations' => [],
                    ];
                    continue;
                }

                if (!is_array($project) || !isset($project['path']) || !is_string($project['path'])) {
                    throw new \InvalidArgumentException('Each inventory project must be a path or a mapping with a path.');
                }

                $id = isset($project['id']) && is_string($project['id']) ? $project['id'] : null;
                $expectations = isset($project['expectations']) && is_array($project['expectations'])
                    ? $project['expectations']
                    : [];
                $definitions[] = [
                    'id' => $id,
                    'path' => $this->resolvePath($project['path'], $inventoryDirectory),
                    'expectations' => $expectations,
                ];
            }
        }

        if ($definitions === []) {
            $definitions[] = ['id' => null, 'path' => $workingDirectory, 'expectations' => []];
        }

        $targets = [];
        $seenIds = [];

        foreach ($definitions as $definition) {
            $path = $this->resolvePath($definition['path'], $workingDirectory);
            $id = $definition['id'] ?? basename(rtrim($path, DIRECTORY_SEPARATOR));

            if (isset($seenIds[$id])) {
                throw new \InvalidArgumentException(sprintf('Duplicate project ID: %s', $id));
            }

            $seenIds[$id] = true;
            $expectations = $definition['expectations'];
            $targets[] = new ProjectTarget($id, $path, $expectations);
        }

        return $targets;
    }

    /**
     * @return array{id: ?string, path: string, expectations: array<string, mixed>}
     */
    private function parseCommandLineDefinition(string $value): array
    {
        if (preg_match('/^([a-zA-Z0-9_.-]+)=(.+)$/', $value, $matches) === 1) {
            return ['id' => $matches[1], 'path' => $matches[2], 'expectations' => []];
        }

        return ['id' => null, 'path' => $value, 'expectations' => []];
    }

    private function resolvePath(string $path, string $baseDirectory): string
    {
        if ($path === '~' || str_starts_with($path, '~/')) {
            $home = getenv('HOME');
            if (is_string($home) && $home !== '') {
                $path = $home . substr($path, 1);
            }
        }

        if (!str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $path = rtrim($baseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
        }

        return realpath($path) ?: $path;
    }
}

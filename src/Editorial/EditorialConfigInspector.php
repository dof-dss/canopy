<?php

declare(strict_types=1);

namespace Canopy\Editorial;

use Symfony\Component\Yaml\Yaml;

final class EditorialConfigInspector
{
    public function inspect(EditorialSite $site): EditorialSnapshot
    {
        $extensions = $this->parse($site->configPath . '/core.extension.yml');
        $modules = isset($extensions['module']) && is_array($extensions['module'])
            ? array_values(array_filter(array_keys($extensions['module']), 'is_string'))
            : [];
        sort($modules);

        $nodeRevisionDefaults = [];
        foreach ($this->files($site->configPath . '/node.type.*.yml') as $path) {
            $config = $this->parse($path);
            $id = isset($config['type']) && is_string($config['type']) ? $config['type'] : $this->configId($path, 'node.type.');
            $nodeRevisionDefaults[$id] = ($config['new_revision'] ?? false) === true;
        }
        ksort($nodeRevisionDefaults);

        $workflows = [];
        foreach ($this->files($site->configPath . '/workflows.workflow.*.yml') as $path) {
            $config = $this->parse($path);
            if (($config['status'] ?? false) !== true || ($config['type'] ?? null) !== 'content_moderation') {
                continue;
            }

            $settings = isset($config['type_settings']) && is_array($config['type_settings']) ? $config['type_settings'] : [];
            $states = isset($settings['states']) && is_array($settings['states']) ? array_keys($settings['states']) : [];
            $transitions = isset($settings['transitions']) && is_array($settings['transitions']) ? array_keys($settings['transitions']) : [];
            $entityTypes = isset($settings['entity_types']) && is_array($settings['entity_types']) ? $settings['entity_types'] : [];
            $bundles = isset($entityTypes['node']) && is_array($entityTypes['node'])
                ? array_values(array_filter($entityTypes['node'], 'is_string'))
                : [];
            sort($states);
            sort($transitions);
            sort($bundles);
            $workflows[] = [
                'id' => isset($config['id']) && is_string($config['id']) ? $config['id'] : $this->configId($path, 'workflows.workflow.'),
                'states' => array_values(array_filter($states, 'is_string')),
                'transitions' => array_values(array_filter($transitions, 'is_string')),
                'bundles' => $bundles,
            ];
        }

        $mediaTypes = $this->ids($site->configPath . '/media.type.*.yml', 'media.type.');
        $fieldNames = $this->fieldNames($site->configPath);
        $pathautoPatterns = $this->ids($site->configPath . '/pathauto.pattern.*.yml', 'pathauto.pattern.');
        $metatagDefaults = $this->ids($site->configPath . '/metatag.metatag_defaults.*.yml', 'metatag.metatag_defaults.');

        $rolePermissions = [];
        foreach ($this->files($site->configPath . '/user.role.*.yml') as $path) {
            $config = $this->parse($path);
            $id = isset($config['id']) && is_string($config['id']) ? $config['id'] : $this->configId($path, 'user.role.');
            $permissions = isset($config['permissions']) && is_array($config['permissions'])
                ? array_values(array_filter($config['permissions'], 'is_string'))
                : [];
            $rolePermissions[$id] = $permissions;
        }
        ksort($rolePermissions);

        return new EditorialSnapshot(
            $site,
            $modules,
            $nodeRevisionDefaults,
            $workflows,
            $mediaTypes,
            $rolePermissions,
            $fieldNames,
            $pathautoPatterns,
            $metatagDefaults,
        );
    }

    /** @return array<string, mixed> */
    private function parse(string $path): array
    {
        $parsed = Yaml::parseFile($path, Yaml::PARSE_CUSTOM_TAGS);
        return is_array($parsed) ? $parsed : [];
    }

    /** @return list<string> */
    private function files(string $pattern): array
    {
        $files = glob($pattern) ?: [];
        sort($files);
        return $files;
    }

    /** @return list<string> */
    private function ids(string $pattern, string $prefix): array
    {
        $ids = array_map(fn (string $path): string => $this->configId($path, $prefix), $this->files($pattern));
        sort($ids);
        return $ids;
    }

    /** @return list<string> */
    private function fieldNames(string $configPath): array
    {
        $names = [];
        foreach ($this->files($configPath . '/field.field.node.*.yml') as $path) {
            $config = $this->parse($path);
            if (isset($config['field_name']) && is_string($config['field_name'])) {
                $names[] = $config['field_name'];
            }
        }
        $names = array_values(array_unique($names));
        sort($names);
        return $names;
    }

    private function configId(string $path, string $prefix): string
    {
        $filename = basename($path, '.yml');
        return str_starts_with($filename, $prefix) ? substr($filename, strlen($prefix)) : $filename;
    }
}

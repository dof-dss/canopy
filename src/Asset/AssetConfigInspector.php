<?php

declare(strict_types=1);

namespace Canopy\Asset;

use Canopy\Editorial\EditorialSite;
use Symfony\Component\Yaml\Yaml;

final class AssetConfigInspector
{
    public function inspect(EditorialSite $site): AssetSnapshot
    {
        $extensions = $this->parse($site->configPath . '/core.extension.yml');
        $modules = isset($extensions['module']) && is_array($extensions['module']) ? array_values(array_filter(array_keys($extensions['module']), 'is_string')) : [];
        sort($modules);
        $mediaTypes = [];
        foreach ($this->files($site->configPath . '/media.type.*.yml') as $path) {
            $config = $this->parse($path);
            $id = isset($config['id']) && is_string($config['id']) ? $config['id'] : $this->configId($path, 'media.type.');
            $sourceConfiguration = isset($config['source_configuration']) && is_array($config['source_configuration']) ? $config['source_configuration'] : [];
            $mediaTypes[$id] = [
                'source' => isset($config['source']) && is_string($config['source']) ? $config['source'] : '',
                'source_field' => isset($sourceConfiguration['source_field']) && is_string($sourceConfiguration['source_field']) ? $sourceConfiguration['source_field'] : '',
            ];
        }
        ksort($mediaTypes);
        $fieldStorages = [];
        foreach ($this->files($site->configPath . '/field.storage.*.yml') as $path) {
            $config = $this->parse($path);
            if (!isset($config['type']) || !is_string($config['type'])) {
                continue;
            }
            $settings = isset($config['settings']) && is_array($config['settings']) ? $config['settings'] : [];
            $id = isset($config['id']) && is_string($config['id']) ? $config['id'] : $this->configId($path, 'field.storage.');
            $fieldStorages[$id] = [
                'entity_type' => isset($config['entity_type']) && is_string($config['entity_type']) ? $config['entity_type'] : '',
                'field_name' => isset($config['field_name']) && is_string($config['field_name']) ? $config['field_name'] : '',
                'type' => $config['type'],
                'uri_scheme' => isset($settings['uri_scheme']) && is_string($settings['uri_scheme']) ? $settings['uri_scheme'] : '',
            ];
        }
        ksort($fieldStorages);
        $uploadExtensions = [];
        foreach ($this->files($site->configPath . '/field.field.*.yml') as $path) {
            $config = $this->parse($path);
            $entityType = isset($config['entity_type']) && is_string($config['entity_type']) ? $config['entity_type'] : '';
            $fieldName = isset($config['field_name']) && is_string($config['field_name']) ? $config['field_name'] : '';
            $storage = $fieldStorages[$entityType . '.' . $fieldName] ?? null;
            if (!is_array($storage) || !in_array($storage['type'], ['file', 'image'], true)) {
                continue;
            }
            $settings = isset($config['settings']) && is_array($config['settings']) ? $config['settings'] : [];
            $id = isset($config['id']) && is_string($config['id']) ? $config['id'] : basename($path, '.yml');
            $value = isset($settings['file_extensions']) && is_string($settings['file_extensions']) ? trim($settings['file_extensions']) : '';
            $uploadExtensions[$id] = $value === '' ? [] : (preg_split('/\s+/', $value) ?: []);
        }
        ksort($uploadExtensions);
        return new AssetSnapshot($site, $modules, $mediaTypes, $fieldStorages, $uploadExtensions, $this->ids($site->configPath . '/image.style.*.yml', 'image.style.'), $this->ids($site->configPath . '/responsive_image.styles.*.yml', 'responsive_image.styles.'));
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

    private function configId(string $path, string $prefix): string
    {
        $filename = basename($path, '.yml');
        return str_starts_with($filename, $prefix) ? substr($filename, strlen($prefix)) : $filename;
    }
}

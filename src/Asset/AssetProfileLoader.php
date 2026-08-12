<?php

declare(strict_types=1);

namespace Canopy\Asset;

use Symfony\Component\Yaml\Yaml;

final class AssetProfileLoader
{
    public function load(string $path): AssetProfile
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException(sprintf('Asset profile does not exist: %s', $path));
        }
        $parsed = Yaml::parseFile($path);
        if (!is_array($parsed) || !isset($parsed['id'], $parsed['capabilities']) || !is_string($parsed['id']) || !is_array($parsed['capabilities'])) {
            throw new \InvalidArgumentException('Asset profile requires an id and capabilities mapping.');
        }
        $capabilities = [];
        foreach ($parsed['capabilities'] as $id => $definition) {
            if (!is_string($id) || !is_array($definition) || !isset($definition['label'], $definition['expectation'], $definition['detector'])
                || !is_string($definition['label']) || !is_string($definition['expectation']) || !is_string($definition['detector'])) {
                throw new \InvalidArgumentException(sprintf('Invalid asset capability definition: %s', is_string($id) ? $id : 'unknown'));
            }
            if (!in_array($definition['expectation'], ['required', 'preferred', 'optional'], true)) {
                throw new \InvalidArgumentException(sprintf('Invalid expectation for asset capability %s.', $id));
            }
            $capabilities[$id] = [
                'label' => $definition['label'],
                'expectation' => $definition['expectation'],
                'detector' => $definition['detector'],
                'values' => isset($definition['values']) && is_array($definition['values']) ? array_values(array_filter($definition['values'], 'is_string')) : [],
            ];
        }
        return new AssetProfile($parsed['id'], isset($parsed['label']) && is_string($parsed['label']) ? $parsed['label'] : $parsed['id'], $capabilities);
    }
}

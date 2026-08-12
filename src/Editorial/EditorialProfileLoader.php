<?php

declare(strict_types=1);

namespace Canopy\Editorial;

use Symfony\Component\Yaml\Yaml;

final class EditorialProfileLoader
{
    public function load(string $path): EditorialProfile
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException(sprintf('Editorial profile does not exist: %s', $path));
        }

        $parsed = Yaml::parseFile($path);
        if (!is_array($parsed) || !isset($parsed['id'], $parsed['capabilities'])
            || !is_string($parsed['id']) || !is_array($parsed['capabilities'])) {
            throw new \InvalidArgumentException('Editorial profile requires an id and capabilities mapping.');
        }

        $capabilities = [];
        foreach ($parsed['capabilities'] as $id => $definition) {
            if (!is_string($id) || !is_array($definition)
                || !isset($definition['label'], $definition['expectation'], $definition['detector'])
                || !is_string($definition['label'])
                || !is_string($definition['expectation'])
                || !is_string($definition['detector'])) {
                throw new \InvalidArgumentException(sprintf('Invalid editorial capability definition: %s', is_string($id) ? $id : 'unknown'));
            }

            if (!in_array($definition['expectation'], ['required', 'preferred', 'optional'], true)) {
                throw new \InvalidArgumentException(sprintf('Invalid expectation for editorial capability %s.', $id));
            }

            $values = isset($definition['values']) && is_array($definition['values'])
                ? array_values(array_filter($definition['values'], 'is_string'))
                : [];
            $exclude = isset($definition['exclude']) && is_array($definition['exclude'])
                ? array_values(array_filter($definition['exclude'], 'is_string'))
                : [];
            $capabilities[$id] = [
                'label' => $definition['label'],
                'expectation' => $definition['expectation'],
                'detector' => $definition['detector'],
                'values' => $values,
                'exclude' => $exclude,
            ];
        }

        return new EditorialProfile(
            $parsed['id'],
            isset($parsed['label']) && is_string($parsed['label']) ? $parsed['label'] : $parsed['id'],
            $capabilities,
        );
    }
}

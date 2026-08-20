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

        $definitions = $parsed['capabilities'];
        $toolbarPolicies = $parsed['ckeditor5_toolbars'] ?? [];
        if (!is_array($toolbarPolicies)) {
            throw new \InvalidArgumentException('Editorial profile ckeditor5_toolbars must be a mapping.');
        }
        foreach ($toolbarPolicies as $format => $policy) {
            if (!is_string($format) || !is_array($policy)) {
                throw new \InvalidArgumentException('Each CKEditor toolbar policy must be a format-to-policy mapping.');
            }
            $id = $format . '_toolbar';
            if (isset($definitions[$id])) {
                throw new \InvalidArgumentException(sprintf('Duplicate editorial capability definition: %s', $id));
            }
            $definitions[$id] = array_merge($policy, [
                'label' => isset($policy['label']) && is_string($policy['label'])
                    ? $policy['label']
                    : sprintf('%s CKEditor toolbar', $format),
                'expectation' => isset($policy['expectation']) && is_string($policy['expectation'])
                    ? $policy['expectation']
                    : 'required',
                'detector' => 'ckeditor5_toolbar',
                'format' => $format,
            ]);
        }

        $textFormatPolicies = $parsed['text_formats'] ?? [];
        if (!is_array($textFormatPolicies)) {
            throw new \InvalidArgumentException('Editorial profile text_formats must be a mapping.');
        }
        foreach ($textFormatPolicies as $format => $policy) {
            if (!is_string($format) || !is_array($policy)) {
                throw new \InvalidArgumentException('Each text format policy must be a format-to-policy mapping.');
            }
            $id = $format . '_text_format';
            if (isset($definitions[$id])) {
                throw new \InvalidArgumentException(sprintf('Duplicate editorial capability definition: %s', $id));
            }
            $definitions[$id] = array_merge($policy, [
                'label' => isset($policy['label']) && is_string($policy['label'])
                    ? $policy['label']
                    : sprintf('%s text format', $format),
                'expectation' => isset($policy['expectation']) && is_string($policy['expectation'])
                    ? $policy['expectation']
                    : 'required',
                'detector' => 'text_format',
                'format' => $format,
                'core' => $policy['core_filters'] ?? [],
                'optional' => $policy['optional_filters'] ?? [],
            ]);
        }

        $capabilities = [];
        foreach ($definitions as $id => $definition) {
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
            $format = isset($definition['format']) && is_string($definition['format']) ? $definition['format'] : '';
            [$core] = $this->policyValues($definition['core'] ?? []);
            [$optional, $optionalReasons] = $this->policyValues($definition['optional'] ?? []);
            $unexpected = isset($definition['unexpected']) && is_string($definition['unexpected'])
                ? $definition['unexpected']
                : 'fail';
            if (in_array($definition['detector'], ['ckeditor5_toolbar', 'text_format'], true)) {
                if ($format === '' || $core === []) {
                    throw new \InvalidArgumentException(sprintf('Editorial policy capability %s requires a format and non-empty core list.', $id));
                }
                if (!in_array($unexpected, ['fail', 'allow'], true)) {
                    throw new \InvalidArgumentException(sprintf('Invalid unexpected-item policy for editorial capability %s.', $id));
                }
                $overlap = array_values(array_intersect($core, $optional));
                if ($overlap !== []) {
                    throw new \InvalidArgumentException(sprintf('CKEditor toolbar capability %s lists items as both core and optional: %s.', $id, implode(', ', $overlap)));
                }
            }
            $capabilities[$id] = [
                'label' => $definition['label'],
                'expectation' => $definition['expectation'],
                'detector' => $definition['detector'],
                'values' => $values,
                'exclude' => $exclude,
                'format' => $format,
                'core' => $core,
                'optional' => $optional,
                'optional_reasons' => $optionalReasons,
                'unexpected' => $unexpected,
            ];
        }

        return new EditorialProfile(
            $parsed['id'],
            isset($parsed['label']) && is_string($parsed['label']) ? $parsed['label'] : $parsed['id'],
            $capabilities,
        );
    }

    /**
     * Accept either a simple list or an item-to-reason mapping.
     *
     * @return array{list<string>, array<string, string>}
     */
    private function policyValues(mixed $configured): array
    {
        if (!is_array($configured)) {
            return [[], []];
        }

        $values = [];
        $reasons = [];
        foreach ($configured as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $values[] = $value;
            } elseif (is_string($key) && is_string($value)) {
                $values[] = $key;
                $reasons[$key] = $value;
            }
        }
        $values = array_values(array_unique($values));
        sort($values);
        ksort($reasons);
        return [$values, $reasons];
    }
}

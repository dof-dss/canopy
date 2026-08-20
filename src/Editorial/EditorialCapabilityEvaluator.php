<?php

declare(strict_types=1);

namespace Canopy\Editorial;

final class EditorialCapabilityEvaluator
{
    /**
     * @param list<string> $values
     * @param list<string> $exclude
     * @param list<string> $core
     * @param list<string> $optional
     * @param array<string, string> $optionalReasons
     */
    public function evaluate(
        string $detector,
        array $values,
        EditorialSnapshot $snapshot,
        array $exclude = [],
        string $format = '',
        array $core = [],
        array $optional = [],
        string $unexpected = 'fail',
        array $optionalReasons = [],
    ): CapabilityObservation
    {
        return match ($detector) {
            'moderated_bundles_revisioned' => $this->moderatedBundlesRevisioned($snapshot, $exclude),
            'moderation_workflow' => $this->moderationWorkflow($snapshot),
            'workflow_states' => $this->workflowValues($snapshot, $values, 'states'),
            'workflow_transitions' => $this->workflowValues($snapshot, $values, 'transitions'),
            'role_separation' => $this->roleSeparation($snapshot),
            'media_library' => $this->mediaLibrary($snapshot, $values),
            'modules' => $this->containsValues('module', $snapshot->modules, $values),
            'any_module' => $this->containsAnyValue('module', $snapshot->modules, $values),
            'field_name_fragments' => $this->fieldFragments($snapshot, $values),
            'config_patterns' => $this->configPatterns($snapshot, $values),
            'text_format' => $this->textFormat($snapshot, $format, $core, $optional, $unexpected, $optionalReasons),
            'ckeditor5_toolbar' => $this->ckeditor5Toolbar($snapshot, $format, $core, $optional, $unexpected, $optionalReasons),
            default => throw new \InvalidArgumentException(sprintf('Unknown editorial capability detector: %s', $detector)),
        };
    }

    /**
     * @param list<string> $core
     * @param list<string> $optional
     * @param array<string, string> $optionalReasons
     */
    private function ckeditor5Toolbar(
        EditorialSnapshot $snapshot,
        string $format,
        array $core,
        array $optional,
        string $unexpectedPolicy,
        array $optionalReasons,
    ): CapabilityObservation {
        $observed = $snapshot->ckeditor5Toolbars[$format] ?? [];
        $missingCore = array_values(array_diff($core, $observed));
        $presentCore = array_values(array_intersect($core, $observed));
        $presentOptional = array_values(array_intersect($optional, $observed));
        $absentOptional = array_values(array_diff($optional, $observed));
        $unexpected = array_values(array_diff($observed, $core, $optional));
        sort($missingCore);
        sort($presentCore);
        sort($presentOptional);
        sort($absentOptional);
        sort($unexpected);
        $registeredVariations = $this->variationReasons($presentOptional, $optionalReasons);
        $variationSuffix = $presentOptional === [] ? '' : ' Registered variations: ' . implode(', ', $presentOptional) . '.';

        $satisfied = isset($snapshot->ckeditor5Toolbars[$format])
            && $missingCore === []
            && ($unexpectedPolicy === 'allow' || $unexpected === []);
        if (!isset($snapshot->ckeditor5Toolbars[$format])) {
            $summary = sprintf('No enabled CKEditor 5 configuration was discovered for text format %s.', $format);
        } elseif ($missingCore !== []) {
            $summary = sprintf('Missing core CKEditor toolbar items for %s: %s.', $format, implode(', ', $missingCore)) . $variationSuffix;
        } elseif ($unexpectedPolicy === 'fail' && $unexpected !== []) {
            $summary = sprintf('Unexpected CKEditor toolbar items for %s: %s.', $format, implode(', ', $unexpected)) . $variationSuffix;
        } else {
            $summary = sprintf(
                'CKEditor toolbar items for %s match policy.%s',
                $format,
                $variationSuffix,
            );
        }

        return new CapabilityObservation($satisfied, $summary, [
            'format' => $format,
            'core' => ['present' => $presentCore, 'missing' => $missingCore],
            'optional' => [
                'present' => $presentOptional,
                'absent' => $absentOptional,
                'registered_variations' => $registeredVariations,
            ],
            'unexpected' => ['policy' => $unexpectedPolicy, 'items' => $unexpected],
            'observed' => $observed,
        ]);
    }

    /**
     * @param list<string> $core
     * @param list<string> $optional
     * @param array<string, string> $optionalReasons
     */
    private function textFormat(
        EditorialSnapshot $snapshot,
        string $format,
        array $core,
        array $optional,
        string $unexpectedPolicy,
        array $optionalReasons,
    ): CapabilityObservation {
        $definition = $snapshot->textFormats[$format] ?? null;
        $observed = $definition['filters'] ?? [];
        $missingCore = array_values(array_diff($core, $observed));
        $presentCore = array_values(array_intersect($core, $observed));
        $presentOptional = array_values(array_intersect($optional, $observed));
        $absentOptional = array_values(array_diff($optional, $observed));
        $unexpected = array_values(array_diff($observed, $core, $optional));
        sort($missingCore);
        sort($presentCore);
        sort($presentOptional);
        sort($absentOptional);
        sort($unexpected);
        $registeredVariations = $this->variationReasons($presentOptional, $optionalReasons);
        $variationSuffix = $presentOptional === [] ? '' : ' Registered variations: ' . implode(', ', $presentOptional) . '.';

        $enabled = ($definition['enabled'] ?? false) === true;
        $satisfied = $enabled
            && $missingCore === []
            && ($unexpectedPolicy === 'allow' || $unexpected === []);
        if ($definition === null) {
            $summary = sprintf('Text format %s was not discovered.', $format);
        } elseif (!$enabled) {
            $summary = sprintf('Text format %s is disabled.', $format);
        } elseif ($missingCore !== []) {
            $summary = sprintf('Text format %s is missing core filters: %s.', $format, implode(', ', $missingCore)) . $variationSuffix;
        } elseif ($unexpectedPolicy === 'fail' && $unexpected !== []) {
            $summary = sprintf('Text format %s has unexpected enabled filters: %s.', $format, implode(', ', $unexpected)) . $variationSuffix;
        } else {
            $summary = sprintf(
                'Text format %s matches policy.%s',
                $format,
                $variationSuffix,
            );
        }

        return new CapabilityObservation($satisfied, $summary, [
            'format' => $format,
            'enabled' => $enabled,
            'core_filters' => ['present' => $presentCore, 'missing' => $missingCore],
            'optional_filters' => [
                'present' => $presentOptional,
                'absent' => $absentOptional,
                'registered_variations' => $registeredVariations,
            ],
            'unexpected_filters' => ['policy' => $unexpectedPolicy, 'items' => $unexpected],
            'observed_filters' => $observed,
        ]);
    }

    /** @param list<string> $requiredPatterns */
    private function configPatterns(EditorialSnapshot $snapshot, array $requiredPatterns): CapabilityObservation
    {
        $matches = [];
        $missing = [];
        foreach ($requiredPatterns as $pattern) {
            $patternMatches = array_values(array_filter(
                $snapshot->configEntities,
                static fn (string $id): bool => fnmatch($pattern, $id),
            ));
            if ($patternMatches === []) {
                $missing[] = $pattern;
            } else {
                $matches[$pattern] = $patternMatches;
            }
        }

        return new CapabilityObservation(
            $missing === [],
            $missing === []
                ? 'All required exported configuration patterns are present.'
                : sprintf('Missing exported configuration patterns: %s.', implode(', ', $missing)),
            ['required_patterns' => $requiredPatterns, 'matches' => $matches, 'missing_patterns' => $missing],
        );
    }

    /**
     * @param list<string> $present
     * @param array<string, string> $reasons
     *
     * @return array<string, string>
     */
    private function variationReasons(array $present, array $reasons): array
    {
        return array_intersect_key($reasons, array_fill_keys($present, true));
    }

    /** @param list<string> $exclude */
    private function moderatedBundlesRevisioned(EditorialSnapshot $snapshot, array $exclude): CapabilityObservation
    {
        $moderated = [];
        foreach ($snapshot->workflows as $workflow) {
            array_push($moderated, ...$workflow['bundles']);
        }
        $moderated = array_values(array_unique($moderated));
        sort($moderated);
        $evaluated = array_values(array_diff($moderated, $exclude));
        $missing = array_values(array_filter(
            $evaluated,
            static fn (string $bundle): bool => ($snapshot->nodeRevisionDefaults[$bundle] ?? false) !== true,
        ));
        $satisfied = $evaluated !== [] && $missing === [];

        return new CapabilityObservation(
            $satisfied,
            $satisfied
                ? sprintf('All %d evaluated moderated content type(s) create revisions by default.', count($evaluated))
                : sprintf('%d moderated content type(s) are missing or do not create revisions by default.', count($missing)),
            ['moderated_bundles' => $moderated, 'evaluated_bundles' => $evaluated, 'excluded_bundles' => $exclude, 'non_revisioned_or_missing' => $missing],
        );
    }

    private function moderationWorkflow(EditorialSnapshot $snapshot): CapabilityObservation
    {
        $ids = array_column($snapshot->workflows, 'id');
        return new CapabilityObservation(
            $ids !== [],
            $ids !== [] ? sprintf('Discovered %d enabled content moderation workflow(s).', count($ids)) : 'No enabled content moderation workflow was discovered.',
            ['workflows' => $ids],
        );
    }

    /** @param list<string> $required */
    private function workflowValues(EditorialSnapshot $snapshot, array $required, string $key): CapabilityObservation
    {
        $observed = [];
        foreach ($snapshot->workflows as $workflow) {
            $values = $key === 'states' ? $workflow['states'] : $workflow['transitions'];
            array_push($observed, ...$values);
        }
        $observed = array_values(array_unique($observed));
        sort($observed);
        $missing = array_values(array_diff($required, $observed));

        return new CapabilityObservation(
            $missing === [],
            $missing === []
                ? sprintf('All expected moderation %s are present.', $key)
                : sprintf('Missing moderation %s: %s.', $key, implode(', ', $missing)),
            ['required' => $required, 'observed' => $observed, 'missing' => $missing],
        );
    }

    private function roleSeparation(EditorialSnapshot $snapshot): CapabilityObservation
    {
        $submitters = [];
        $publishers = [];
        foreach ($snapshot->rolePermissions as $role => $permissions) {
            $canSubmit = $this->hasTransition($permissions, 'submit_for_review');
            $canPublish = $this->hasTransition($permissions, 'publish') || $this->hasTransition($permissions, 'quick_publish');
            if ($canSubmit && !$canPublish) {
                $submitters[] = $role;
            }
            if ($canPublish) {
                $publishers[] = $role;
            }
        }

        $satisfied = $submitters !== [] && $publishers !== [];
        return new CapabilityObservation(
            $satisfied,
            $satisfied ? 'Separate submitter and publisher role capabilities are configured.' : 'Could not establish separate submitter and publisher roles.',
            ['submitter_only_roles' => $submitters, 'publisher_roles' => $publishers],
        );
    }

    /** @param list<string> $permissions */
    private function hasTransition(array $permissions, string $transition): bool
    {
        foreach ($permissions as $permission) {
            if (preg_match('/^use .+ transition ' . preg_quote($transition, '/') . '$/', $permission) === 1) {
                return true;
            }
        }
        return false;
    }

    /** @param list<string> $required */
    private function mediaLibrary(EditorialSnapshot $snapshot, array $required): CapabilityObservation
    {
        $missingModules = array_values(array_diff(['media', 'media_library'], $snapshot->modules));
        $missingTypes = array_values(array_diff($required, $snapshot->mediaTypes));
        $satisfied = $missingModules === [] && $missingTypes === [];
        return new CapabilityObservation(
            $satisfied,
            $satisfied ? 'Reusable image and document media capabilities are configured.' : 'Media library modules or required media types are missing.',
            [
                'media_types' => $snapshot->mediaTypes,
                'missing_modules' => $missingModules,
                'missing_media_types' => $missingTypes,
            ],
        );
    }

    /**
     * @param list<string> $observed
     * @param list<string> $required
     */
    private function containsValues(string $kind, array $observed, array $required): CapabilityObservation
    {
        $missing = array_values(array_diff($required, $observed));
        return new CapabilityObservation(
            $missing === [],
            $missing === [] ? sprintf('Required %s configuration is present.', $kind) : sprintf('Missing %s configuration: %s.', $kind, implode(', ', $missing)),
            ['required' => $required, 'observed' => $observed, 'missing' => $missing],
        );
    }

    /**
     * @param list<string> $observed
     * @param list<string> $alternatives
     */
    private function containsAnyValue(string $kind, array $observed, array $alternatives): CapabilityObservation
    {
        $matches = array_values(array_intersect($alternatives, $observed));
        return new CapabilityObservation(
            $matches !== [],
            $matches !== []
                ? sprintf('A supported %s implementation is present: %s.', $kind, implode(', ', $matches))
                : sprintf('None of the supported %s implementations are present: %s.', $kind, implode(', ', $alternatives)),
            ['alternatives' => $alternatives, 'observed' => $observed, 'matches' => $matches],
        );
    }

    /** @param list<string> $fragments */
    private function fieldFragments(EditorialSnapshot $snapshot, array $fragments): CapabilityObservation
    {
        $matches = [];
        foreach ($snapshot->fieldNames as $fieldName) {
            foreach ($fragments as $fragment) {
                if (str_contains($fieldName, $fragment)) {
                    $matches[] = $fieldName;
                }
            }
        }
        $matches = array_values(array_unique($matches));
        sort($matches);
        return new CapabilityObservation(
            $matches !== [],
            $matches !== [] ? sprintf('Discovered %d content review-date field(s).', count($matches)) : 'No content review-date fields were discovered.',
            ['field_name_fragments' => $fragments, 'matching_fields' => $matches],
        );
    }
}

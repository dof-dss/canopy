<?php

declare(strict_types=1);

namespace Canopy\Editorial;

final readonly class EditorialSnapshot
{
    /**
     * @param list<string> $modules
     * @param array<string, bool> $nodeRevisionDefaults
     * @param list<array{id: string, states: list<string>, transitions: list<string>, bundles: list<string>}> $workflows
     * @param list<string> $mediaTypes
     * @param array<string, list<string>> $rolePermissions
     * @param list<string> $fieldNames
     * @param list<string> $pathautoPatterns
     * @param list<string> $metatagDefaults
     */
    public function __construct(
        public EditorialSite $site,
        public array $modules,
        public array $nodeRevisionDefaults,
        public array $workflows,
        public array $mediaTypes,
        public array $rolePermissions,
        public array $fieldNames,
        public array $pathautoPatterns,
        public array $metatagDefaults,
    ) {
    }
}

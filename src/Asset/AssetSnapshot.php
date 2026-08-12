<?php

declare(strict_types=1);

namespace Canopy\Asset;

use Canopy\Editorial\EditorialSite;

final readonly class AssetSnapshot
{
    /**
     * @param list<string> $modules
     * @param array<string, array{source: string, source_field: string}> $mediaTypes
     * @param array<string, array{entity_type: string, field_name: string, type: string, uri_scheme: string}> $fieldStorages All field storage, used to validate media source references; file/image types are audited further.
     * @param array<string, list<string>> $uploadExtensions
     * @param list<string> $imageStyles
     * @param list<string> $responsiveImageStyles
     */
    public function __construct(
        public EditorialSite $site,
        public array $modules,
        public array $mediaTypes,
        public array $fieldStorages,
        public array $uploadExtensions,
        public array $imageStyles,
        public array $responsiveImageStyles,
    ) {
    }
}

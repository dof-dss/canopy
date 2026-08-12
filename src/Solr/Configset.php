<?php

declare(strict_types=1);

namespace Canopy\Solr;

final readonly class Configset
{
    /**
     * @param array<string, string> $fileHashes
     * @param array<string, string> $properties
     */
    public function __construct(
        public string $name,
        public string $path,
        public string $relativePath,
        public string $source,
        public array $fileHashes,
        public string $fingerprint,
        public string $manifestFingerprint,
        public array $properties,
    ) {
    }
}

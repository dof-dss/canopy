<?php

declare(strict_types=1);

namespace Canopy\Solr;

final class ServiceVersionInspector
{
    /**
     * @return list<array{version: string, sources: list<string>}>
     */
    public function inspect(string $projectRoot): array
    {
        $files = [
            '.platform/services.yaml' => '/^\s*type:\s*[\'\"]?solr:(\d+(?:\.\d+)*)/m',
            '.ddev/docker-compose.solr.yaml' => '/^\s*(?:image:|SOLR_BASE_IMAGE:)[^\n]*\bsolr:(\d+(?:\.\d+)*)/m',
            '.ddev/solr/Dockerfile' => '/^\s*(?:FROM|ARG\s+[a-zA-Z0-9_]+=)[^\n]*\bsolr:(\d+(?:\.\d+)*)/mi',
        ];
        $versions = [];

        foreach ($files as $relativePath => $pattern) {
            $path = $projectRoot . '/' . $relativePath;
            if (!is_file($path)) {
                continue;
            }

            $contents = file_get_contents($path);
            if ($contents === false) {
                continue;
            }

            preg_match_all($pattern, $contents, $matches);
            foreach (array_unique($matches[1]) as $version) {
                $versions[$version][] = $relativePath;
            }
        }

        $declarations = [];
        foreach ($versions as $version => $sources) {
            $declarations[] = ['version' => (string) $version, 'sources' => $sources];
        }
        usort(
            $declarations,
            static fn (array $left, array $right): int => version_compare($left['version'], $right['version']),
        );

        return $declarations;
    }
}

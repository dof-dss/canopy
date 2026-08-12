<?php

declare(strict_types=1);

namespace Canopy\Solr;

final readonly class ConfigsetDiscovery
{
    public function __construct(private ConfigsetInspector $inspector = new ConfigsetInspector())
    {
    }

    /**
     * @return list<Configset>
     */
    public function discover(string $projectRoot): array
    {
        $candidates = [];
        $multiPaths = glob($projectRoot . '/.platform/solr_configsets/*/conf', GLOB_ONLYDIR) ?: [];

        foreach ($multiPaths as $path) {
            $candidates[] = [
                'name' => basename(dirname($path)),
                'path' => $path,
                'source' => 'hosted',
            ];
        }

        $hostedPaths = glob($projectRoot . '/.platform/solr*_config', GLOB_ONLYDIR) ?: [];
        foreach ($hostedPaths as $path) {
            if ($this->looksLikeConfigset($path)) {
                $candidates[] = ['name' => 'default', 'path' => $path, 'source' => 'hosted'];
            }
        }

        $localPath = $projectRoot . '/.ddev/solr/conf';
        if ($this->looksLikeConfigset($localPath)) {
            $candidates[] = ['name' => 'default', 'path' => $localPath, 'source' => 'local'];
        }

        $localConfigsets = glob($projectRoot . '/.ddev/solr/configsets/*', GLOB_ONLYDIR) ?: [];
        foreach ($localConfigsets as $path) {
            $confPath = is_dir($path . '/conf') ? $path . '/conf' : $path;
            if ($this->looksLikeConfigset($confPath)) {
                $candidates[] = ['name' => basename($path), 'path' => $confPath, 'source' => 'local'];
            }
        }

        $configsets = [];
        $seen = [];

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate['path']) ?: $candidate['path'];
            if (isset($seen[$realPath])) {
                continue;
            }

            $seen[$realPath] = true;
            $configsets[] = $this->inspector->inspect(
                $candidate['name'],
                $realPath,
                $candidate['source'],
                $projectRoot,
            );
        }

        usort(
            $configsets,
            static fn (Configset $left, Configset $right): int => [$left->name, $left->source] <=> [$right->name, $right->source],
        );

        return $configsets;
    }

    private function looksLikeConfigset(string $path): bool
    {
        return is_dir($path) && (is_file($path . '/schema.xml') || is_file($path . '/solrconfig.xml'));
    }
}

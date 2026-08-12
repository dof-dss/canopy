<?php

declare(strict_types=1);

namespace Canopy\Solr;

final class ConfigsetInspector
{
    public function inspect(string $name, string $path, string $source, string $projectRoot): Configset
    {
        $fileHashes = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getFilename() === '.gitmanaged') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen(rtrim($path, DIRECTORY_SEPARATOR)) + 1);
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            $hash = hash_file('sha256', $file->getPathname());

            if ($hash !== false) {
                $fileHashes[$relative] = $hash;
            }
        }

        ksort($fileHashes);
        $fingerprintMaterial = '';

        foreach ($fileHashes as $relative => $hash) {
            $fingerprintMaterial .= $relative . "\0" . $hash . "\n";
        }

        $properties = [];
        $propertiesPath = rtrim($path, DIRECTORY_SEPARATOR) . '/solrcore.properties';

        if (is_file($propertiesPath)) {
            $lines = file($propertiesPath, FILE_IGNORE_NEW_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);
                $properties[trim($key)] = trim($value);
            }
        }

        $relativePath = str_starts_with($path, rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
            ? substr($path, strlen(rtrim($projectRoot, DIRECTORY_SEPARATOR)) + 1)
            : $path;

        return new Configset(
            $name,
            $path,
            str_replace(DIRECTORY_SEPARATOR, '/', $relativePath),
            $source,
            $fileHashes,
            hash('sha256', $fingerprintMaterial),
            hash('sha256', implode("\n", array_keys($fileHashes))),
            $properties,
        );
    }
}

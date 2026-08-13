<?php

declare(strict_types=1);

namespace Canopy\Solr;

use Canopy\Inventory\ProjectTarget;
use Canopy\Result\Result;
use Canopy\Result\Status;
use Canopy\Security\OutputRedactor;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final readonly class SolrAuditor
{
    private const REQUIRED_FILES = ['schema.xml', 'solrconfig.xml'];

    public function __construct(
        private ConfigsetDiscovery $discovery = new ConfigsetDiscovery(),
        private ServiceVersionInspector $serviceVersions = new ServiceVersionInspector(),
        private OutputRedactor $redactor = new OutputRedactor(),
    ) {
    }

    public function audit(ProjectTarget $project, bool $runProjectVerifier): ProjectAudit
    {
        $observedAt = new \DateTimeImmutable();

        if (!is_dir($project->path) || !is_readable($project->path)) {
            return new ProjectAudit($project, [], [new Result(
                'solr.project.access',
                Status::Unknown,
                $project->id,
                'The project directory is missing or unreadable.',
                $observedAt,
                ['path' => $project->path],
                'Correct the inventory path or filesystem permissions.',
            )]);
        }

        $configsets = $this->discovery->discover($project->path);
        $results = [$this->auditServiceVersions($project, $observedAt)];

        if ($configsets === []) {
            $results[] = new Result(
                'solr.configset.discovery',
                Status::Unknown,
                $project->id,
                'No committed Solr configset source was discovered.',
                $observedAt,
                ['searched_layouts' => [
                    '.platform/solr_configsets/*/conf',
                    '.platform/solr*_config',
                    '.ddev/solr/conf',
                    '.ddev/solr/configsets/*',
                ]],
                'Document or add the repository-owned configset source before assessing its consistency.',
            );
        } else {
            $results[] = new Result(
                'solr.configset.discovery',
                Status::Pass,
                $project->id,
                sprintf('Discovered %d committed Solr configset source(s).', count($configsets)),
                $observedAt,
                ['configsets' => array_map(
                    static fn (Configset $configset): array => [
                        'name' => $configset->name,
                        'source' => $configset->source,
                        'path' => $configset->relativePath,
                    ],
                    $configsets,
                )],
            );
        }

        foreach ($configsets as $configset) {
            array_push($results, ...$this->auditConfigset($project, $configset, $observedAt));
        }

        array_push($results, ...$this->auditSourceParity($project, $configsets, $observedAt));
        array_push($results, ...$this->runNativeVerifier($project, $runProjectVerifier, $observedAt));

        return new ProjectAudit($project, $configsets, $results);
    }

    private function auditServiceVersions(ProjectTarget $project, \DateTimeImmutable $observedAt): Result
    {
        $versions = $this->serviceVersions->inspect($project->path);
        $solrExpectations = $project->expectations['solr'] ?? [];
        $expectedVersion = is_array($solrExpectations) && isset($solrExpectations['version'])
            && is_scalar($solrExpectations['version'])
            ? (string) $solrExpectations['version']
            : null;

        if ($versions === []) {
            return new Result(
                'solr.service.version_declarations',
                Status::Unknown,
                $project->id,
                'No static Solr service version declarations were discovered.',
                $observedAt,
                ['expected_version' => $expectedVersion, 'versions' => []],
                'Inspect the project service definitions and document the expected Solr version.',
            );
        }

        $declaredVersions = array_column($versions, 'version');
        $expectedPresent = $expectedVersion === null || in_array($expectedVersion, $declaredVersions, true);
        $status = match (true) {
            !$expectedPresent => Status::Fail,
            count($versions) > 1 => Status::Warn,
            default => Status::Pass,
        };
        $summary = match ($status) {
            Status::Fail => sprintf('Expected Solr %s is not present in the discovered service declarations.', $expectedVersion),
            Status::Warn => sprintf('Repository service files contain multiple Solr versions: %s.', implode(', ', $declaredVersions)),
            default => sprintf('Repository service files consistently declare Solr %s.', $declaredVersions[0]),
        };

        return new Result(
            'solr.service.version_declarations',
            $status,
            $project->id,
            $summary,
            $observedAt,
            ['expected_version' => $expectedVersion, 'versions' => $versions],
            $status === Status::Pass ? null : 'Reconcile or document version differences between hosted and local service definitions.',
        );
    }

    /**
     * @return list<Result>
     */
    private function auditConfigset(ProjectTarget $project, Configset $configset, \DateTimeImmutable $observedAt): array
    {
        $missingFiles = array_values(array_filter(
            self::REQUIRED_FILES,
            static fn (string $file): bool => !isset($configset->fileHashes[$file]),
        ));
        $target = sprintf('%s:%s:%s', $project->id, $configset->name, $configset->source);
        $results = [new Result(
            'solr.configset.completeness',
            $missingFiles === [] ? Status::Pass : Status::Fail,
            $target,
            $missingFiles === []
                ? sprintf('Configset contains the required files and %d fingerprinted file(s).', count($configset->fileHashes))
                : sprintf('Configset is missing required file(s): %s.', implode(', ', $missingFiles)),
            $observedAt,
            [
                'path' => $configset->relativePath,
                'source' => $configset->source,
                'file_count' => count($configset->fileHashes),
                'fingerprint_sha256' => $configset->fingerprint,
                'manifest_sha256' => $configset->manifestFingerprint,
                'missing_files' => $missingFiles,
            ],
            $missingFiles === [] ? null : 'Restore or regenerate the configset from an authoritative compatible source.',
        )];

        $luceneVersion = $configset->properties['solr.luceneMatchVersion'] ?? null;
        $installDirectory = $configset->properties['solr.install.dir'] ?? null;
        $hasProperties = isset($configset->fileHashes['solrcore.properties']);
        $solrExpectations = $project->expectations['solr'] ?? [];
        $expectedLucene = is_array($solrExpectations) && isset($solrExpectations['lucene_match_version'])
            && is_scalar($solrExpectations['lucene_match_version'])
            ? (string) $solrExpectations['lucene_match_version']
            : null;
        $metadataStatus = match (true) {
            $expectedLucene !== null && $luceneVersion !== $expectedLucene => Status::Fail,
            !$hasProperties, $luceneVersion === null => Status::Warn,
            default => Status::Pass,
        };

        $results[] = new Result(
            'solr.configset.compatibility_metadata',
            $metadataStatus,
            $target,
            match (true) {
                $expectedLucene !== null && $luceneVersion === null => sprintf('Configset does not declare the expected Lucene %s compatibility.', $expectedLucene),
                $expectedLucene !== null && $luceneVersion !== $expectedLucene => sprintf('Configset declares Lucene %s; the project expects %s.', $luceneVersion, $expectedLucene),
                !$hasProperties => 'Configset has no solrcore.properties compatibility metadata.',
                $luceneVersion === null => 'Configset does not declare solr.luceneMatchVersion.',
                default => sprintf('Configset declares Lucene %s compatibility.', $luceneVersion),
            },
            $observedAt,
            [
                'lucene_match_version' => $luceneVersion,
                'expected_lucene_match_version' => $expectedLucene,
                'solr_install_dir' => $installDirectory,
            ],
            $metadataStatus === Status::Pass
                ? null
                : 'Confirm compatibility against the configured Solr service version.',
        );

        return $results;
    }

    /**
     * @param list<Configset> $configsets
     *
     * @return list<Result>
     */
    private function auditSourceParity(ProjectTarget $project, array $configsets, \DateTimeImmutable $observedAt): array
    {
        $byName = [];
        foreach ($configsets as $configset) {
            $byName[$configset->name][] = $configset;
        }

        $results = [];
        foreach ($byName as $name => $sources) {
            if (count($sources) < 2) {
                continue;
            }

            $reference = $sources[0];
            foreach (array_slice($sources, 1) as $candidate) {
                $allFiles = array_unique(array_merge(array_keys($reference->fileHashes), array_keys($candidate->fileHashes)));
                sort($allFiles);
                $differentFiles = array_values(array_filter(
                    $allFiles,
                    static fn (string $file): bool => ($reference->fileHashes[$file] ?? null) !== ($candidate->fileHashes[$file] ?? null),
                ));
                $matches = $differentFiles === [];

                $results[] = new Result(
                    'solr.configset.source_parity',
                    $matches ? Status::Pass : Status::Warn,
                    sprintf('%s:%s', $project->id, $name),
                    $matches
                        ? sprintf('%s and %s configset sources match.', $reference->source, $candidate->source)
                        : sprintf('%s and %s configset sources differ in %d file(s).', $reference->source, $candidate->source, count($differentFiles)),
                    $observedAt,
                    [
                        'reference' => $reference->relativePath,
                        'candidate' => $candidate->relativePath,
                        'different_files' => array_slice($differentFiles, 0, 100),
                        'different_file_count' => count($differentFiles),
                    ],
                    $matches ? null : 'Confirm whether the hosted and local differences are intentional and document their ownership.',
                );
            }
        }

        return $results;
    }

    /**
     * @return list<Result>
     */
    private function runNativeVerifier(ProjectTarget $project, bool $run, \DateTimeImmutable $observedAt): array
    {
        $relativePath = 'scripts/solr/verify-configsets';
        $script = $project->path . '/' . $relativePath;

        if (!$run) {
            return [new Result(
                'solr.project_verifier',
                Status::Skipped,
                $project->id,
                'Project-owned Solr verifier was not executed.',
                $observedAt,
                ['path' => $relativePath, 'available' => is_file($script)],
                'Pass --run-project-verifier to explicitly execute a repository-owned verifier.',
            )];
        }

        if (!is_file($script)) {
            return [new Result(
                'solr.project_verifier',
                Status::Skipped,
                $project->id,
                'No project-owned Solr verifier is available.',
                $observedAt,
                ['path' => $relativePath, 'available' => false],
            )];
        }

        if (!is_executable($script)) {
            return [new Result(
                'solr.project_verifier',
                Status::Unknown,
                $project->id,
                'The project-owned Solr verifier is not executable.',
                $observedAt,
                ['path' => $relativePath],
                'Review the tracked executable bit before running the verifier.',
            )];
        }

        if (is_link($script)) {
            return [new Result(
                'solr.project_verifier',
                Status::Unknown,
                $project->id,
                'The project-owned Solr verifier is a symbolic link and was not executed.',
                $observedAt,
                ['path' => $relativePath],
                'Replace the symbolic link with a reviewed, repository-owned executable file.',
            )];
        }

        $startedAt = microtime(true);
        $process = new Process([$script], $project->path, $this->restrictedEnvironment());
        $process->setTimeout(120);

        try {
            $exitCode = $process->run();
        } catch (ProcessFailedException | \RuntimeException $exception) {
            return [new Result(
                'solr.project_verifier',
                Status::Unknown,
                $project->id,
                'The project-owned Solr verifier could not complete.',
                $observedAt,
                ['path' => $relativePath, 'error_type' => $exception::class],
                'Run the verifier directly to inspect its local execution requirements.',
                (int) round((microtime(true) - $startedAt) * 1000),
            )];
        }

        $lines = preg_split('/\R/', trim($process->getOutput() . "\n" . $process->getErrorOutput())) ?: [];
        $notices = [];
        $errors = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'NOTICE: ')) {
                $notices[] = $this->redactor->text(substr($line, 8), [$project->path]);
            }
            if (str_starts_with($line, 'ERROR: ')) {
                $errors[] = $this->redactor->text(substr($line, 7), [$project->path]);
            }
        }

        $duration = (int) round((microtime(true) - $startedAt) * 1000);
        $failureSummary = sprintf('Project-owned Solr verifier failed with exit code %d.', $exitCode);
        if ($errors !== []) {
            $failureSummary .= ' ' . $errors[0] . '.';
        }
        $results = [new Result(
            'solr.project_verifier',
            $exitCode === 0 ? Status::Pass : Status::Fail,
            $project->id,
            $exitCode === 0
                ? 'Project-owned Solr verifier passed.'
                : $failureSummary,
            $observedAt,
            [
                'path' => $relativePath,
                'exit_code' => $exitCode,
                'notice_count' => count($notices),
                'errors' => array_slice($errors, 0, 100),
            ],
            $exitCode === 0 ? null : 'Run the project verifier directly and resolve its deterministic errors.',
            $duration,
        )];

        foreach ($notices as $notice) {
            $results[] = new Result(
                'solr.project_verifier.notice',
                Status::Warn,
                $project->id,
                $notice,
                $observedAt,
                ['source' => $relativePath],
            );
        }

        return $results;
    }

    /** @return array<string, string|false> */
    private function restrictedEnvironment(): array
    {
        $environment = getenv();
        $restricted = array_fill_keys(array_keys($environment), false);
        $path = getenv('PATH');

        $restricted['PATH'] = is_string($path) && $path !== '' ? $path : '/usr/local/bin:/usr/bin:/bin';
        $restricted['LANG'] = 'C';
        $restricted['LC_ALL'] = 'C';
        $restricted['CANOPY_VERIFIER_MODE'] = 'read-only';

        return $restricted;
    }
}

<?php

declare(strict_types=1);

namespace Canopy\Solr;

use Canopy\Result\Result;
use Canopy\Result\Status;

final class EstateSummary
{
    /**
     * @param list<ProjectAudit> $audits
     *
     * @return list<Result>
     */
    public function results(array $audits): array
    {
        $observedAt = new \DateTimeImmutable();
        $manifestVariants = [];
        $luceneVersions = [];
        $missingLucene = [];
        $configsetCount = 0;

        foreach ($audits as $audit) {
            foreach ($audit->configsets as $configset) {
                ++$configsetCount;
                $target = sprintf('%s:%s:%s', $audit->project->id, $configset->name, $configset->source);
                $manifestVariants[$configset->manifestFingerprint][] = $target;
                $lucene = $configset->properties['solr.luceneMatchVersion'] ?? null;

                if ($lucene === null) {
                    $missingLucene[] = $target;
                } else {
                    $luceneVersions[$lucene][] = $target;
                }
            }
        }

        $manifestStatus = match (true) {
            $configsetCount === 0 => Status::Unknown,
            count($manifestVariants) === 1 => Status::Pass,
            default => Status::Warn,
        };
        $versionStatus = match (true) {
            $configsetCount === 0, $luceneVersions === [] => Status::Unknown,
            count($luceneVersions) === 1 && $missingLucene === [] => Status::Pass,
            default => Status::Warn,
        };

        return [
            new Result(
                'solr.estate.configset_structure',
                $manifestStatus,
                'estate',
                match ($manifestStatus) {
                    Status::Pass => sprintf('All %d configsets share one file manifest.', $configsetCount),
                    Status::Warn => sprintf('%d configsets use %d different file manifests.', $configsetCount, count($manifestVariants)),
                    default => 'No configset manifests were available for comparison.',
                },
                $observedAt,
                ['variants' => $manifestVariants],
                $manifestStatus === Status::Warn
                    ? 'Review structural variants before assuming configsets were generated from a consistent baseline.'
                    : null,
            ),
            new Result(
                'solr.estate.lucene_compatibility',
                $versionStatus,
                'estate',
                match ($versionStatus) {
                    Status::Pass => sprintf('All configsets declare Lucene %s compatibility.', array_key_first($luceneVersions)),
                    Status::Warn => sprintf('The estate contains %d declared Lucene version(s) and %d configset(s) without a declaration.', count($luceneVersions), count($missingLucene)),
                    default => 'No Lucene compatibility declarations were available for comparison.',
                },
                $observedAt,
                [
                    'versions' => $luceneVersions,
                    'missing' => $missingLucene,
                ],
                $versionStatus === Status::Warn
                    ? 'Compare each configset declaration with its configured Solr service version.'
                    : null,
            ),
        ];
    }
}

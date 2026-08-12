<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit\Solr;

use Canopy\Inventory\ProjectTarget;
use Canopy\Result\Status;
use Canopy\Solr\SolrAuditor;
use PHPUnit\Framework\TestCase;

final class SolrAuditorTest extends TestCase
{
    public function testAuditsModernConfigsetWithoutExecutingRepositoryCode(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/modern';
        $audit = (new SolrAuditor())->audit(new ProjectTarget('modern', $root), false);

        self::assertCount(1, $audit->configsets);
        self::assertSame(Status::Pass, $this->statusFor($audit->results, 'solr.configset.completeness'));
        self::assertSame(Status::Pass, $this->statusFor($audit->results, 'solr.configset.compatibility_metadata'));
        self::assertSame(Status::Skipped, $this->statusFor($audit->results, 'solr.project_verifier'));
    }

    public function testWarnsWhenLegacyHostedAndLocalSourcesDiffer(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/legacy';
        $audit = (new SolrAuditor())->audit(new ProjectTarget('legacy', $root), false);

        self::assertSame(Status::Warn, $this->statusFor($audit->results, 'solr.configset.source_parity'));
    }

    public function testFailsCompatibilityMetadataAgainstAnExplicitExpectation(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/modern';
        $project = new ProjectTarget('modern', $root, [
            'solr' => [
                'lucene_match_version' => '10.0.0',
            ],
        ]);
        $audit = (new SolrAuditor())->audit($project, false);

        self::assertSame(Status::Fail, $this->statusFor($audit->results, 'solr.configset.compatibility_metadata'));
    }

    public function testExplicitlyRunsAndClassifiesAProjectOwnedVerifier(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/modern';
        $audit = (new SolrAuditor())->audit(new ProjectTarget('modern', $root), true);

        self::assertSame(Status::Pass, $this->statusFor($audit->results, 'solr.project_verifier'));
        self::assertSame(Status::Warn, $this->statusFor($audit->results, 'solr.project_verifier.notice'));
    }

    /**
     * @param list<\Canopy\Result\Result> $results
     */
    private function statusFor(array $results, string $checkId): Status
    {
        foreach ($results as $result) {
            if ($result->checkId === $checkId) {
                return $result->status;
            }
        }

        self::fail(sprintf('No result found for %s.', $checkId));
    }
}

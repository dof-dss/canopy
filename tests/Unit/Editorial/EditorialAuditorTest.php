<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit\Editorial;

use Canopy\Editorial\EditorialAuditor;
use Canopy\Editorial\EditorialProfileLoader;
use Canopy\Inventory\ProjectTarget;
use Canopy\Result\Status;
use PHPUnit\Framework\TestCase;

final class EditorialAuditorTest extends TestCase
{
    public function testEvaluatesRequiredPreferredAndOptionalCapabilities(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/editorial';
        $profile = (new EditorialProfileLoader())->load(dirname(__DIR__, 3) . '/config/editorial/nics.yml');
        $results = (new EditorialAuditor())->audit([new ProjectTarget('example', $root)], $profile);

        self::assertSame(Status::Pass, $this->statusFor($results, 'editorial.capability.revision_history'));
        self::assertSame(Status::Pass, $this->statusFor($results, 'editorial.capability.role_separation'));
        self::assertSame(Status::Pass, $this->statusFor($results, 'editorial.capability.review_scheduling'));
        self::assertSame(Status::Pass, $this->statusFor($results, 'editorial.capability.scheduled_publishing'));
        self::assertSame(Status::Pass, $this->statusFor($results, 'editorial.capability.concurrent_editing_protection'));
    }

    /** @param list<\Canopy\Result\Result> $results */
    private function statusFor(array $results, string $checkId): Status
    {
        foreach ($results as $result) {
            if ($result->checkId === $checkId) {
                return $result->status;
            }
        }
        self::fail(sprintf('Missing result %s.', $checkId));
    }
}

<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit\Asset;

use Canopy\Asset\AssetAuditor;
use Canopy\Asset\AssetProfileLoader;
use Canopy\Inventory\ProjectTarget;
use Canopy\Result\Status;
use PHPUnit\Framework\TestCase;

final class AssetAuditorTest extends TestCase
{
    public function testEvaluatesAssetCapabilitiesAgainstOneDiscoveredSite(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/assets';
        $profile = (new AssetProfileLoader())->load(dirname(__DIR__, 3) . '/config/assets/nics.yml');
        $results = (new AssetAuditor())->audit([new ProjectTarget('example', $root)], $profile);

        $capabilities = array_values(array_filter($results, static fn ($result): bool => str_starts_with($result->checkId, 'assets.capability.')));
        self::assertCount(6, $capabilities);
        foreach ($capabilities as $result) {
            self::assertSame(Status::Pass, $result->status, $result->checkId);
            self::assertSame('example:example', $result->target);
        }
    }
}

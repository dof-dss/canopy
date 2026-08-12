<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit\Editorial;

use Canopy\Editorial\EditorialSiteDiscovery;
use Canopy\Inventory\ProjectTarget;
use PHPUnit\Framework\TestCase;

final class EditorialSiteDiscoveryTest extends TestCase
{
    public function testDiscoversRootDrupalConfiguration(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/editorial';
        $sites = (new EditorialSiteDiscovery())->discover(new ProjectTarget('example', $root));

        self::assertCount(1, $sites);
        self::assertSame('example', $sites[0]->id);
        self::assertSame('config/sync', $sites[0]->relativeConfigPath);
    }
}

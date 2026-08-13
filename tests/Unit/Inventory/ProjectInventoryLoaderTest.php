<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit\Inventory;

use Canopy\Inventory\ProjectInventoryLoader;
use PHPUnit\Framework\TestCase;

final class ProjectInventoryLoaderTest extends TestCase
{
    public function testLoadsRelativeInventoryPathsAndStableIds(): void
    {
        $fixtures = dirname(__DIR__, 2) . '/Fixtures';
        $targets = (new ProjectInventoryLoader())->load([], $fixtures . '/inventory.yml', $fixtures);

        self::assertCount(2, $targets);
        self::assertSame('modern', $targets[0]->id);
        self::assertSame(realpath($fixtures . '/modern'), $targets[0]->path);
        self::assertSame('legacy', $targets[1]->id);
    }

    public function testRejectsDuplicateProjectIds(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate project ID: example');

        (new ProjectInventoryLoader())->load(
            ['example=/first', 'example=/second'],
            null,
            '/',
        );
    }

    public function testAutomaticallyLoadsProjectLocalCanopyInventory(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/project-local-inventory';
        $targets = (new ProjectInventoryLoader())->load([], null, $root);

        self::assertCount(1, $targets);
        self::assertSame('consumer-project', $targets[0]->id);
        self::assertSame(realpath($root), $targets[0]->path);
        self::assertSame('8.11', $targets[0]->expectations['solr']['version']);
    }

    public function testExplicitProjectTakesPrecedenceOverProjectLocalInventory(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/project-local-inventory';
        $targets = (new ProjectInventoryLoader())->load(['explicit=' . $root], null, $root);

        self::assertCount(1, $targets);
        self::assertSame('explicit', $targets[0]->id);
        self::assertSame([], $targets[0]->expectations);
    }
}

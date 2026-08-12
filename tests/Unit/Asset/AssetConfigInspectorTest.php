<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit\Asset;

use Canopy\Asset\AssetConfigInspector;
use Canopy\Editorial\EditorialSite;
use Canopy\Inventory\ProjectTarget;
use PHPUnit\Framework\TestCase;

final class AssetConfigInspectorTest extends TestCase
{
    public function testBuildsPerSiteAssetInventoryFromExportedConfig(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/assets';
        $project = new ProjectTarget('example', $root);
        $snapshot = (new AssetConfigInspector())->inspect(new EditorialSite($project, 'demo', $root . '/config/sync', 'config/sync'));

        self::assertSame(['document', 'image'], array_keys($snapshot->mediaTypes));
        self::assertSame('field_media_image', $snapshot->mediaTypes['image']['source_field']);
        self::assertSame('private', $snapshot->fieldStorages['media.field_media_file']['uri_scheme']);
        self::assertSame(['large'], $snapshot->imageStyles);
        self::assertSame(['article'], $snapshot->responsiveImageStyles);
    }
}

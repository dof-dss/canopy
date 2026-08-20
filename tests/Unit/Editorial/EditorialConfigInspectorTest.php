<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit\Editorial;

use Canopy\Editorial\EditorialConfigInspector;
use Canopy\Editorial\EditorialSiteDiscovery;
use Canopy\Inventory\ProjectTarget;
use PHPUnit\Framework\TestCase;

final class EditorialConfigInspectorTest extends TestCase
{
    public function testBuildsBoundedEditorialSnapshotFromExportedConfig(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/editorial';
        $site = (new EditorialSiteDiscovery())->discover(new ProjectTarget('example', $root))[0];
        $snapshot = (new EditorialConfigInspector())->inspect($site);

        self::assertTrue($snapshot->nodeRevisionDefaults['article']);
        self::assertSame(['document', 'image'], $snapshot->mediaTypes);
        self::assertSame(['article', 'webform'], $snapshot->workflows[0]['bundles']);
        self::assertContains('field_next_audit_due', $snapshot->fieldNames);
        self::assertArrayHasKey('author', $snapshot->rolePermissions);
        self::assertContains('heading', $snapshot->ckeditor5Toolbars['basic_html']);
        self::assertContains('insertTable', $snapshot->ckeditor5Toolbars['basic_html']);
        self::assertNotContains('|', $snapshot->ckeditor5Toolbars['basic_html']);
        self::assertTrue($snapshot->textFormats['basic_html']['enabled']);
        self::assertContains('filter_image_lazy_load', $snapshot->textFormats['basic_html']['filters']);
        self::assertContains('diff.settings', $snapshot->configEntities);
    }
}

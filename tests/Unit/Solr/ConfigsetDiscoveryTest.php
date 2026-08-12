<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit\Solr;

use Canopy\Solr\ConfigsetDiscovery;
use PHPUnit\Framework\TestCase;

final class ConfigsetDiscoveryTest extends TestCase
{
    public function testDiscoversModernPerSiteConfigsetAndCompatibilityMetadata(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/modern';
        $configsets = (new ConfigsetDiscovery())->discover($root);

        self::assertCount(1, $configsets);
        self::assertSame('example', $configsets[0]->name);
        self::assertSame('hosted', $configsets[0]->source);
        self::assertSame('9.12.2', $configsets[0]->properties['solr.luceneMatchVersion']);
        self::assertCount(3, $configsets[0]->fileHashes);
    }

    public function testDiscoversDistinctHostedAndLocalLegacySources(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/legacy';
        $configsets = (new ConfigsetDiscovery())->discover($root);

        self::assertCount(2, $configsets);
        self::assertSame(['hosted', 'local'], array_column($configsets, 'source'));
        self::assertNotSame($configsets[0]->fingerprint, $configsets[1]->fingerprint);
    }
}

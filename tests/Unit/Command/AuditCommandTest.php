<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit\Command;

use Canopy\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class AuditCommandTest extends TestCase
{
    public function testEmitsSchemaCompatibleJsonForAStaticSolrAudit(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/modern';
        $command = (new Application())->find('audit');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'pack' => 'solr',
            '--project' => ['modern=' . $root],
            '--format' => 'json',
        ]);

        $document = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertSame('1.0', $document['schema_version']);
        self::assertSame('solr_audit', $document['kind']);
        self::assertSame('modern', $document['project']['name']);
        self::assertSame(1, $document['summary']['skipped']);
        self::assertNotEmpty($document['results']);
    }

    public function testEmitsEditorialCapabilityAuditJson(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/editorial';
        $command = (new Application())->find('audit');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'pack' => 'editorial',
            '--project' => ['example=' . $root],
            '--format' => 'json',
        ]);

        $document = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertSame('editorial_capability_audit', $document['kind']);
        self::assertSame(0, $document['summary']['fail']);
        self::assertNotEmpty($document['results']);
    }

    public function testEmitsPerSiteMediaFileAssetAuditJson(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/assets';
        $tester = new CommandTester((new Application())->find('audit'));
        $exitCode = $tester->execute([
            'pack' => 'assets',
            '--project' => ['demo=' . $root],
            '--format' => 'json',
        ]);
        $document = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertSame('media_file_asset_audit', $document['kind']);
        self::assertSame(0, $document['summary']['fail']);
        self::assertSame('demo:demo', $document['results'][1]['target']);
    }
}

<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit\Solr;

use Canopy\Solr\ServiceVersionInspector;
use PHPUnit\Framework\TestCase;

final class ServiceVersionInspectorTest extends TestCase
{
    public function testFindsServiceVersionsWithoutTreatingSolrPortsAsVersions(): void
    {
        $root = sys_get_temp_dir() . '/canopy-service-version-' . bin2hex(random_bytes(6));
        mkdir($root . '/.ddev', 0777, true);
        file_put_contents($root . '/.ddev/docker-compose.solr.yaml', <<<'YAML'
services:
  solr:
    image: solr:8
    environment:
      URL: http://solr:8983/solr/dev
YAML);

        try {
            $versions = (new ServiceVersionInspector())->inspect($root);
        } finally {
            unlink($root . '/.ddev/docker-compose.solr.yaml');
            rmdir($root . '/.ddev');
            rmdir($root);
        }

        self::assertSame([[
            'version' => '8',
            'sources' => ['.ddev/docker-compose.solr.yaml'],
        ]], $versions);
    }
}

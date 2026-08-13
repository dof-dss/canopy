<?php

declare(strict_types=1);

namespace Canopy\Audit;

final class AuditPackRegistry
{
    /**
     * @var array<string, string>
     */
    private const PACKS = [
        'solr' => 'Inspect Solr versions, configsets, and repository wiring',
        'editorial' => 'Assess exported configuration against an editorial capability profile',
        'assets' => 'Assess media and file-asset configuration',
    ];

    /** @return array<string, string> */
    public function all(): array
    {
        return self::PACKS;
    }

    /** @return list<string> */
    public function ids(): array
    {
        return array_keys(self::PACKS);
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, self::PACKS);
    }
}

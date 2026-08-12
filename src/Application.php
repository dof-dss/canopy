<?php

declare(strict_types=1);

namespace Canopy;

use Canopy\Command\AboutCommand;
use Canopy\Command\AuditCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public const VERSION = '0.2.0-dev';

    public function __construct()
    {
        parent::__construct('Canopy', self::VERSION);

        $this->add(new AboutCommand());
        $this->add(new AuditCommand());
    }
}

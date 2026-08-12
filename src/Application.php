<?php

declare(strict_types=1);

namespace Canopy;

use Canopy\Command\AboutCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct('Canopy', '0.1.0-dev');

        $this->add(new AboutCommand());
    }
}

<?php

declare(strict_types=1);

namespace Canopy\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'about', description: 'Describe Canopy and its safety boundary')]
final class AboutCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            '<info>Canopy</info>',
            'Read-only technical assurance for Drupal codebases.',
            '',
            'Canopy collects evidence and reports findings. It does not remediate them.',
        ]);

        return self::SUCCESS;
    }
}

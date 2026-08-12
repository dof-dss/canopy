<?php

declare(strict_types=1);

namespace Canopy\Output;

use Canopy\Result\Result;
use Canopy\Result\Status;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

final class EditorialConsoleRenderer
{
    /** @param list<Result> $results */
    public function render(OutputInterface $output, array $results): void
    {
        $output->writeln('<info>Canopy editorial capability audit</info>');
        $output->writeln('Exported Drupal configuration only; active configuration and editorial usability were not inspected.');

        $targets = [];
        foreach ($results as $result) {
            if (!str_starts_with($result->checkId, 'editorial.capability.')) {
                continue;
            }
            $targets[$result->target] ??= array_fill_keys(array_column(Status::cases(), 'value'), 0);
            ++$targets[$result->target][$result->status->value];
        }

        $table = new Table($output);
        $table->setHeaders(['Site', 'Pass', 'Warn', 'Fail', 'Unknown', 'Skipped']);
        foreach ($targets as $target => $counts) {
            $table->addRow([$target, $counts['pass'], $counts['warn'], $counts['fail'], $counts['unknown'], $counts['skipped']]);
        }
        $table->render();

        $findings = array_values(array_filter(
            $results,
            static fn (Result $result): bool => $result->status !== Status::Pass,
        ));
        if ($findings === []) {
            $output->writeln("\n<info>No non-passing exported-configuration findings.</info>");
            return;
        }

        $output->writeln("\n<comment>Findings and limitations</comment>");
        $findingsTable = new Table($output);
        $findingsTable->setHeaders(['Status', 'Capability/check', 'Target', 'Summary']);
        foreach ($findings as $finding) {
            $findingsTable->addRow([$finding->status->value, $finding->checkId, $finding->target, $finding->summary]);
        }
        $findingsTable->render();
    }
}

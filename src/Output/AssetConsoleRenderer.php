<?php

declare(strict_types=1);

namespace Canopy\Output;

use Canopy\Result\Result;
use Canopy\Result\Status;
use Canopy\Security\OutputRedactor;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

final class AssetConsoleRenderer
{
    public function __construct(private readonly OutputRedactor $redactor = new OutputRedactor())
    {
    }

    /** @param list<Result> $results */
    public function render(OutputInterface $output, array $results): void
    {
        $output->writeln('<info>Canopy media and file asset audit</info>');
        $output->writeln('Results are isolated per discovered site and use exported Drupal configuration only.');
        $targets = [];
        foreach ($results as $result) {
            if (!str_starts_with($result->checkId, 'assets.capability.')) {
                continue;
            }
            $targets[$result->target] ??= array_fill_keys(array_column(Status::cases(), 'value'), 0);
            ++$targets[$result->target][$result->status->value];
        }
        $table = new Table($output);
        $table->setHeaders(['Site', 'Pass', 'Warn', 'Fail', 'Unknown', 'Skipped']);
        foreach ($targets as $target => $counts) {
            $table->addRow([$this->safe($target), $counts['pass'], $counts['warn'], $counts['fail'], $counts['unknown'], $counts['skipped']]);
        }
        $table->render();
        $findings = array_values(array_filter($results, static fn (Result $result): bool => $result->status !== Status::Pass));
        if ($findings === []) {
            $output->writeln("\n<info>No non-passing exported-configuration findings.</info>");
            return;
        }
        $output->writeln("\n<comment>Findings and limitations</comment>");
        $findingsTable = new Table($output);
        $findingsTable->setHeaders(['Status', 'Capability/check', 'Site', 'Summary']);
        foreach ($findings as $finding) {
            $findingsTable->addRow([
                $this->safe($finding->status->value),
                $this->safe($finding->checkId),
                $this->safe($finding->target),
                $this->safe($finding->summary),
            ]);
        }
        $findingsTable->render();
    }

    private function safe(string $value): string
    {
        return OutputFormatter::escape($this->redactor->text($value));
    }
}

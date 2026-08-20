<?php

declare(strict_types=1);

namespace Canopy\Output;

use Canopy\Result\Result;
use Canopy\Result\Status;
use Canopy\Security\OutputRedactor;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

final class EditorialConsoleRenderer
{
    public function __construct(private readonly OutputRedactor $redactor = new OutputRedactor())
    {
    }

    /** @param list<Result> $results */
    public function render(OutputInterface $output, array $results, bool $detail = false, bool $filtered = false): void
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
            $table->addRow([$this->safe($target), $counts['pass'], $counts['warn'], $counts['fail'], $counts['unknown'], $counts['skipped']]);
        }
        $table->render();

        $findings = $detail
            ? $results
            : array_values(array_filter(
                $results,
                static fn (Result $result): bool => $result->status !== Status::Pass,
            ));
        if ($findings === []) {
            $message = $filtered
                ? 'No exported-configuration results match the selected statuses.'
                : 'No non-passing exported-configuration findings.';
            $output->writeln("\n<info>" . $message . '</info>');
            return;
        }

        $output->writeln($detail
            ? "\n<comment>Detailed results</comment>"
            : "\n<comment>Findings and limitations</comment>");
        $findingsTable = new Table($output);
        $findingsTable->setHeaders(['Status', 'Capability/check', 'Target', 'Summary']);
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

<?php

declare(strict_types=1);

namespace Canopy\Output;

use Canopy\Result\Result;
use Canopy\Result\Status;
use Canopy\Security\OutputRedactor;
use Canopy\Solr\ProjectAudit;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

final class SolrConsoleRenderer
{
    public function __construct(private readonly OutputRedactor $redactor = new OutputRedactor())
    {
    }

    /**
     * @param list<ProjectAudit> $audits
     * @param list<Result> $estateResults
     */
    public function render(OutputInterface $output, array $audits, array $estateResults): void
    {
        $output->writeln('<info>Canopy Solr audit</info>');
        $output->writeln('Static repository evidence only; no DDEV or service runtime checks were performed.');
        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders(['Project', 'Configsets', 'Pass', 'Warn', 'Fail', 'Unknown', 'Skipped']);

        foreach ($audits as $audit) {
            $counts = $this->counts($audit->results);
            $table->addRow([
                $this->safe($audit->project->id),
                count($audit->configsets),
                $counts['pass'],
                $counts['warn'],
                $counts['fail'],
                $counts['unknown'],
                $counts['skipped'],
            ]);
        }

        $table->render();

        $findings = [];
        foreach ($audits as $audit) {
            array_push($findings, ...array_filter(
                $audit->results,
                static fn (Result $result): bool => $result->status !== Status::Pass,
            ));
        }
        array_push($findings, ...array_filter(
            $estateResults,
            static fn (Result $result): bool => $result->status !== Status::Pass,
        ));

        if ($findings === []) {
            $output->writeln("\n<info>No non-passing static findings.</info>");
            return;
        }

        $output->writeln("\n<comment>Findings and limitations</comment>");
        $findingsTable = new Table($output);
        $findingsTable->setHeaders(['Status', 'Check', 'Target', 'Summary']);

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

    /**
     * @param list<Result> $results
     *
     * @return array<string, int>
     */
    private function counts(array $results): array
    {
        $counts = array_fill_keys(array_column(Status::cases(), 'value'), 0);
        foreach ($results as $result) {
            ++$counts[$result->status->value];
        }

        return $counts;
    }

    private function safe(string $value): string
    {
        return OutputFormatter::escape($this->redactor->text($value));
    }
}

<?php

declare(strict_types=1);

namespace Canopy\Command;

use Canopy\Inventory\ProjectInventoryLoader;
use Canopy\Output\ResultDocument;
use Canopy\Output\SolrConsoleRenderer;
use Canopy\Result\Result;
use Canopy\Result\Status;
use Canopy\Solr\EstateSummary;
use Canopy\Solr\SolrAuditor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'audit', description: 'Audit one or more projects')]
final class AuditCommand extends Command
{
    public function __construct(
        private readonly ProjectInventoryLoader $inventoryLoader = new ProjectInventoryLoader(),
        private readonly SolrAuditor $solrAuditor = new SolrAuditor(),
        private readonly EstateSummary $estateSummary = new EstateSummary(),
        private readonly ResultDocument $resultDocument = new ResultDocument(),
        private readonly SolrConsoleRenderer $consoleRenderer = new SolrConsoleRenderer(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('pack', InputArgument::REQUIRED, 'Audit pack to run; currently only solr')
            ->addOption(
                'project',
                'p',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Project path, optionally prefixed with a stable ID as id=/path',
            )
            ->addOption('inventory', 'i', InputOption::VALUE_REQUIRED, 'YAML inventory containing a projects list')
            ->addOption(
                'run-project-verifier',
                null,
                InputOption::VALUE_NONE,
                'Explicitly execute scripts/solr/verify-configsets when present',
            )
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format: console or json', 'console');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pack = $input->getArgument('pack');

        if ($pack !== 'solr') {
            $io->error(sprintf('Unknown audit pack "%s". Available packs: solr.', is_scalar($pack) ? (string) $pack : ''));
            return self::INVALID;
        }

        $format = $input->getOption('format');
        if (!is_string($format) || !in_array($format, ['console', 'json'], true)) {
            $io->error('The --format option must be console or json.');
            return self::INVALID;
        }

        $projectValues = $input->getOption('project');
        $inventoryPath = $input->getOption('inventory');

        try {
            $projects = $this->inventoryLoader->load(
                is_array($projectValues) ? array_values(array_filter($projectValues, 'is_string')) : [],
                is_string($inventoryPath) ? $inventoryPath : null,
                (string) getcwd(),
            );
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());
            return self::INVALID;
        }

        $audits = [];
        $results = [];
        $runProjectVerifier = (bool) $input->getOption('run-project-verifier');

        foreach ($projects as $project) {
            $audit = $this->solrAuditor->audit($project, $runProjectVerifier);
            $audits[] = $audit;
            array_push($results, ...$audit->results);
        }

        $estateResults = $this->estateSummary->results($audits);
        array_push($results, ...$estateResults);

        if ($format === 'json') {
            $document = $this->resultDocument->build('solr_audit', $projects, $results);
            $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $output->writeln($json);
        } else {
            $this->consoleRenderer->render($output, $audits, $estateResults);
        }

        foreach ($results as $result) {
            if (in_array($result->status, [Status::Fail, Status::Unknown], true)) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}

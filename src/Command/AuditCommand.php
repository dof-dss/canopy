<?php

declare(strict_types=1);

namespace Canopy\Command;

use Canopy\Audit\AuditPackRegistry;
use Canopy\Asset\AssetAuditor;
use Canopy\Asset\AssetProfileLoader;
use Canopy\Editorial\EditorialAuditor;
use Canopy\Editorial\EditorialProfileLoader;
use Canopy\Inventory\ProjectInventoryLoader;
use Canopy\Output\AssetConsoleRenderer;
use Canopy\Output\EditorialConsoleRenderer;
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
        private readonly EditorialAuditor $editorialAuditor = new EditorialAuditor(),
        private readonly EditorialProfileLoader $editorialProfileLoader = new EditorialProfileLoader(),
        private readonly EditorialConsoleRenderer $editorialConsoleRenderer = new EditorialConsoleRenderer(),
        private readonly AssetAuditor $assetAuditor = new AssetAuditor(),
        private readonly AssetProfileLoader $assetProfileLoader = new AssetProfileLoader(),
        private readonly AssetConsoleRenderer $assetConsoleRenderer = new AssetConsoleRenderer(),
        private readonly AuditPackRegistry $packRegistry = new AuditPackRegistry(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('pack', InputArgument::OPTIONAL, 'Audit pack to run')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List the available audit packs')
            ->addOption(
                'project',
                'p',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Project path, optionally prefixed with a stable ID as id=/path',
            )
            ->addOption('inventory', 'i', InputOption::VALUE_REQUIRED, 'YAML inventory containing a projects list')
            ->addOption('profile', null, InputOption::VALUE_REQUIRED, 'Capability profile used by the selected audit pack')
            ->addOption(
                'run-project-verifier',
                null,
                InputOption::VALUE_NONE,
                'Execute a reviewed, trusted scripts/solr/verify-configsets with a stripped environment',
            )
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format: console or json', 'console');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pack = $input->getArgument('pack');

        if ((bool) $input->getOption('list')) {
            $io->title('Available audit packs');
            $rows = [];
            foreach ($this->packRegistry->all() as $id => $description) {
                $rows[] = [$id, $description];
            }
            $io->table(['Pack', 'Description'], $rows);
            return self::SUCCESS;
        }

        if (!is_string($pack) || $pack === '') {
            $io->error('Specify an audit pack, or use --list to see the available packs.');
            return self::INVALID;
        }

        if (!$this->packRegistry->has($pack)) {
            $io->error(sprintf(
                'Unknown audit pack "%s". Available packs: %s.',
                $pack,
                implode(', ', $this->packRegistry->ids()),
            ));
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

        if ($pack === 'editorial') {
            return $this->executeEditorial($input, $output, $io, $projects, $format);
        }
        if ($pack === 'assets') {
            return $this->executeAssets($input, $output, $io, $projects, $format);
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

    /** @param list<\Canopy\Inventory\ProjectTarget> $projects */
    private function executeEditorial(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        array $projects,
        string $format,
    ): int {
        $profileOption = $input->getOption('profile');
        $profilePath = is_string($profileOption)
            ? $profileOption
            : dirname(__DIR__, 2) . '/config/editorial/nics.yml';
        if (!str_starts_with($profilePath, DIRECTORY_SEPARATOR)) {
            $profilePath = (string) getcwd() . DIRECTORY_SEPARATOR . $profilePath;
        }

        try {
            $profile = $this->editorialProfileLoader->load($profilePath);
            $results = $this->editorialAuditor->audit($projects, $profile);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());
            return self::INVALID;
        }

        if ($format === 'json') {
            $document = $this->resultDocument->build('editorial_capability_audit', $projects, $results);
            $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $output->writeln($json);
        } else {
            $this->editorialConsoleRenderer->render($output, $results);
        }

        foreach ($results as $result) {
            if (in_array($result->status, [Status::Fail, Status::Unknown], true)) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /** @param list<\Canopy\Inventory\ProjectTarget> $projects */
    private function executeAssets(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        array $projects,
        string $format,
    ): int {
        $profileOption = $input->getOption('profile');
        $profilePath = is_string($profileOption) ? $profileOption : dirname(__DIR__, 2) . '/config/assets/nics.yml';
        if (!str_starts_with($profilePath, DIRECTORY_SEPARATOR)) {
            $profilePath = (string) getcwd() . DIRECTORY_SEPARATOR . $profilePath;
        }
        try {
            $profile = $this->assetProfileLoader->load($profilePath);
            $results = $this->assetAuditor->audit($projects, $profile);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());
            return self::INVALID;
        }
        if ($format === 'json') {
            $document = $this->resultDocument->build('media_file_asset_audit', $projects, $results);
            $output->writeln(json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->assetConsoleRenderer->render($output, $results);
        }
        foreach ($results as $result) {
            if (in_array($result->status, [Status::Fail, Status::Unknown], true)) {
                return self::FAILURE;
            }
        }
        return self::SUCCESS;
    }
}

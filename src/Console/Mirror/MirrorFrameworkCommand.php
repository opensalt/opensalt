<?php

namespace App\Console\Mirror;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ImportCaseJsonCommand;
use App\Service\MirrorFramework;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MirrorFrameworkCommand extends Command
{
    use CommandDispatcherTrait;

    protected static $defaultName = 'mirror:framework';

    /**
     * @var MirrorFramework
     */
    private $mirrorFramework;

    public function __construct(MirrorFramework $mirrorServer, string $name = null)
    {
        parent::__construct($name);
        $this->mirrorFramework = $mirrorServer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Mirror a framework')
            ->addArgument('url', InputArgument::REQUIRED, 'URL of framework to mirror')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $url = $input->getArgument('url');

        $io->comment(sprintf('Mirroring %s', $url));

        $framework = $this->mirrorFramework->fetchFramework($url);
        $jsonDoc = json5_decode($framework, true);

        // If the URL was for a document instead of package then try again
        if (!isset($jsonDoc['CFDocument']) && isset($jsonDoc['CFPackageURI'])) {
            $url = $jsonDoc['CFPackageURI']['uri'];
            $framework = $this->mirrorFramework->fetchFramework($url);
        }

        $command = new ImportCaseJsonCommand($framework);
        $this->sendCommand($command);

        $io->success('Complete');

        return 0;
    }
}

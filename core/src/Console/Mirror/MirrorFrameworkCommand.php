<?php

namespace App\Console\Mirror;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ImportCaseJsonCommand;
use App\Service\MirrorFramework;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('mirror:framework', 'Mirror a framework')]
class MirrorFrameworkCommand extends Command
{
    use CommandDispatcherTrait;

    public function __construct(private readonly MirrorFramework $mirrorFramework)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
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

        return (int) Command::SUCCESS;
    }
}

<?php

namespace App\Console\Mirror;

use App\Service\MirrorFramework;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('jobs:mirror', 'Find and process the next scheduled mirror job')]
class MirrorJobCommand extends Command
{
    public function __construct(private readonly MirrorFramework $mirrorFramework)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->comment('Find and process next mirror job');

        $framework = $this->mirrorFramework->mirrorNext();

        if (null === $framework) {
            $io->comment('Nothing to do');

            return (int) Command::SUCCESS;
        }

        $io->success(sprintf('Updated %s', $framework->getIdentifier()));

        return (int) Command::SUCCESS;
    }
}

<?php

namespace App\Console\Mirror;

use App\Service\MirrorFramework;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MirrorJobCommand extends Command
{
    protected static $defaultName = 'jobs:mirror';

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
            ->setDescription('Find and process the next scheduled mirror job')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->comment('Find and process next mirror job');

        $framework = $this->mirrorFramework->mirrorNext();

        if (null === $framework) {
            $io->comment('Nothing to do');

            return 0;
        }

        $io->success(sprintf('Updated %s', $framework->getIdentifier()));

        return 0;
    }
}

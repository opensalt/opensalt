<?php

namespace App\Console\Mirror;

use App\Service\MirrorServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('jobs:update-next-server', 'Find and process the next scheduled framework list update')]
class UpdateListCommand extends Command
{
    public function __construct(private readonly MirrorServer $mirrorServer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->comment('Find server to check');

        $server = $this->mirrorServer->updateNext();

        if (null === $server) {
            $io->comment('Nothing to do');

            return 0;
        }

        $io->success(sprintf('Updated %s', $server->getUrl()));

        return 0;
    }
}
